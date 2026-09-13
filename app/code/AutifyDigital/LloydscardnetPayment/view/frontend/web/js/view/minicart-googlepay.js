/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
function stripHtml(str) {
    if (!str) return '';
    var tmp = document.createElement('DIV');
    tmp.innerHTML = str;
    return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
}

define([
    'jquery',
    'Magento_Checkout/js/view/minicart',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/url',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
    'mage/cookies',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/full-screen-loader',
    'ko'
], function ($, Component, alert, confirm, url, storage, customerData, priceUtils, cookies, globalMessageList, fullScreenLoader, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            googlePayButtonSelector: '#google-pay-minicart-button',
            grandTotal: 0
        },

        isGooglePayInitialized: ko.observable(false),

        initialize: function () {
            if (this.isCheckoutPage() == false) {
                this.isGooglePayInitialized(false);
                this._super();
                this.initGooglePayIfNeeded();
            }
            return this;
        },
        isCheckoutPage: function() {
            return window.location.pathname.indexOf('/checkout') >= 0 &&
                   $('body').hasClass('checkout-index-index');
        },
        afterRender: function () {
            this._super();
            this.initGooglePayIfNeeded();
        },
        initGooglePayIfNeeded: function () {
            if (this.isGooglePayInitialized()) {
                return;
            }
            if ($(this.googlePayButtonSelector).length) {
                this.initGooglePay();
                this.GooglePayMinicart.init();
                this.isGooglePayInitialized(true);
            } else {
                this.observeDomForGooglePayButton();
            }
        },
        observeDomForGooglePayButton: function () {
            var self = this;

            var observer = new MutationObserver(function (mutations) {
                if ($(self.googlePayButtonSelector).length && !self.isGooglePayInitialized()) {
                    self.initGooglePay();
                    self.GooglePayMinicart.init();
                    self.isGooglePayInitialized(true);
                    observer.disconnect();
                }
            });

            observer.observe(document.body, { childList: true, subtree: true });
            setTimeout(function () {
                if (!self.isGooglePayInitialized() && $(self.googlePayButtonSelector).length) {
                    self.initGooglePay();
                    self.GooglePayMinicart.init();
                    self.isGooglePayInitialized(true);
                    observer.disconnect();
                }
            }, 2000);
        },
        initGooglePay: function () {
            var self = this;

            self.GooglePayMinicart = {
                merchantId: window.googlepayConfig.merchant_id,
                paymentMode: window.googlepayConfig.payment_mode,
                merchantName: window.googlepayConfig.merchant_name,
                currentCurrency: window.googlepayConfig.currency,
                gatewayMerchantId: window.googlepayConfig.gateway_merchant_id,
                supported_networks: window.googlepayConfig.supported_networks ?
                    window.googlepayConfig.supported_networks.split(",") :
                    ["AMEX", "DISCOVER", "MASTERCARD", "VISA"],
                allowedCountries: window.googlepayConfig.allowed_countries ?
                    window.googlepayConfig.allowed_countries.split(",") :
                    ["GB", "CA", "US"],
                grandTotal: self.grandTotal,
                countryCode: 'GB',
                buttonType: 'buy',
                buttonColor: window.googlepayConfig.button_color,
                apiVersion: 2,
                apiVersionMinor: 0,
                googlePayButtonRendered: false,
                paymentsClient: null,
                shippingMethods: [],
                selectedShippingMethod: null,
                loggingEnabled: false,
                buttonSelector: self.googlePayButtonSelector,
                countryLists: null,
                shippingAddress: {},

                init: function () {
                    this.loadGooglePayScript();
                    this.bindEvents();
                    if (!this.countryLists) {
                        storage.get('rest/V1/directory/countries').done(function (result) {
                            this.countryLists = {};
                            let i, data, x, region, name;

                            for (i = 0; i < result.length; ++i) {
                                data = result[i];
                                this.countryLists[data.two_letter_abbreviation] = {};
                                if (typeof data.available_regions === 'undefined') {
                                    continue;
                                }

                                for (x = 0; x < data.available_regions.length; ++x) {
                                    region = data.available_regions[x];
                                    if (region.name) {
                                        name = region.name.toLowerCase().replace(/[^A-Z0-9]/ig, '');
                                        this.countryLists[data.two_letter_abbreviation][name] = region.id;
                                    }
                                    if (region.code) {
                                        this.countryLists[data.two_letter_abbreviation][region.code.toLowerCase()] = region.id;
                                    }
                                }
                            }
                        }.bind(this));
                    }
                },

                handlePaymentCancellation: function () {
                    if (this.paymentsClient) {
                        try {
                            this.paymentsClient = null;
                        } catch (e) {
                            console.log('Error cleaning up payment client:', e);
                        }
                    }
                    this.googlePayButtonRendered = false;
                },

                bindEvents: function () {
                    var gpSelf = this;
                    window.addEventListener('message', function (event) {
                        // SECURITY: Validate message origin to prevent cross-origin attacks
                        if (!event || !event.origin) {
                            return; // Ignore invalid events
                        }
                        
                        var trustedOrigins = [
                            window.location.origin,
                            'https://pay.google.com',
                            'https://pay.sandbox.google.com',
                            'https://pay.google.co.uk',
                            'https://pay.google.ie',
                            'https://payments.google.com',
                            'https://applepay.cdn-apple.com',
                            'https://apple-pay-gateway.apple.com',
                            'https://apple-pay-gateway-cert.apple.com',
                            'https://apple-pay-gateway-nc-pod1.apple.com',
                            'https://apple-pay-gateway-pr-pod1.apple.com'
                        ];
                        
                        // Check if the origin is trusted
                        var isOriginTrusted = false;
                        for (var i = 0; i < trustedOrigins.length; i++) {
                            if (event.origin === trustedOrigins[i]) {
                                isOriginTrusted = true;
                                break;
                            }
                        }
                        
                        if (!isOriginTrusted) {
                            console.warn('Blocked message from untrusted origin:', event.origin);
                            return; // Block untrusted origins
                        }
                        
                        if (event.data && event.data.type === 'CANCELED') {
                            gpSelf.handlePaymentCancellation();
                        }
                    });
                },

                loadGooglePayScript: function () {
                    var gpSelf = this;
                    if (typeof google === "undefined" || !google.payments) {
                        var script = document.createElement('script');
                        script.src = "https://pay.google.com/gp/p/js/pay.js";
                        script.onload = function () {
                            gpSelf.renderGooglePayButton();
                        };
                        script.onerror = function (error) {
                            console.log('Error loading Google Pay script', error);
                        };
                        document.head.appendChild(script);
                    } else {
                        this.renderGooglePayButton();
                    }
                },

                renderGooglePayButton: function () {
                    var gpSelf = this;
                    var checkInterval = setInterval(function () {
                        gpSelf.addGooglePayButton();
                        gpSelf.googlePayButtonRendered = true;
                        clearInterval(checkInterval);
                    }, 1000);

                    setTimeout(function () {
                        clearInterval(checkInterval);
                    }, 10000);
                },

                getGooglePaymentsClient: function () {
                    if (!this.paymentsClient) {
                        this.paymentsClient = new google.payments.api.PaymentsClient({
                            environment: this.paymentMode,
                            paymentDataCallbacks: {
                                onPaymentDataChanged: this.onPaymentDataChanged.bind(this),
                                onPaymentAuthorized: this.onPaymentAuthorized.bind(this)
                            }
                        });
                    }
                    return this.paymentsClient;
                },

                onPaymentAuthorized: function (paymentData) {
                    //Place Order and Payment request
                    var self = this;

                    return new Promise(function (resolve, reject) {
                        let shippingContact = paymentData.shippingAddress,
                            billingContact = paymentData.paymentMethodData?.info?.billingAddress || {};
                        const email = paymentData.email || '';
                        billingContact.email = email;

                        const formKey = $.mage.cookies.get('form_key');

                        // Find selected shipping method
                        let shippingOptionId = paymentData.shippingOptionData?.id;
                        let selectedShippingMethod = self.shippingMethods?.[shippingOptionId];

                        if (!selectedShippingMethod) {
                            console.error('Shipping method not found:', shippingOptionId);
                            globalMessageList.addErrorMessage({ message: $t('Invalid shipping method selected.') });

                            resolve({
                                transactionState: 'ERROR',
                                error: {
                                    reason: 'SHIPPING_ADDRESS_UNSERVICEABLE',
                                    message: 'Invalid shipping method selected.',
                                    intent: 'SHIPPING_ADDRESS'
                                }
                            });
                            return;
                        }

                        let shippingMethodCode = selectedShippingMethod.carrier_code + '_' + selectedShippingMethod.method_code;

                        fullScreenLoader.startLoader();

                        var browser_width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
                        var browser_height = window.innerHeight || document.documentElement.clientHeight|| document.body.clientHeight;

                        // Step 1: Create order
                        $.ajax({
                            url: url.build('lloyds/wallet/googlepaycreateorder'),
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                form_key: formKey,
                                shippingContact: shippingContact,
                                billingContact: billingContact,
                                shipping_method: shippingMethodCode,
                                payment_method: 'cardnetgooglepay'
                            }),
                            success: function(data) {
                                if (data.success) {
                                    // Step 2: Process payment
                                    $.ajax({
                                        url: url.build('lloyds/googlepay/payment'),
                                        type: 'POST',
                                        dataType: 'json',
                                        data: {
                                            form_key: formKey,
                                            screen_height: browser_height,
                                            screen_width: browser_width,
                                            paymentData: JSON.stringify(paymentData)
                                        }
                                    }).done(function (response) {
                                        fullScreenLoader.stopLoader();
                                        
                                        if (response) {
                                            if (response['url']) {
                                                $('body').trigger('processStart');
                                                // Validate redirect URL to prevent open redirect attacks
                                                var redirectUrl = response['url'];
                                                if (!redirectUrl || typeof redirectUrl !== 'string') {
                                                    console.error('Invalid redirect URL');
                                                    reject({ error: 'INVALID_REDIRECT' });
                                                    return;
                                                }
                                                
                                                // Only allow same-origin or trusted payment URLs
                                                try {
                                                    var parsedUrl = new URL(redirectUrl, window.location.origin);
                                                    var trustedDomains = [
                                                        window.location.origin,
                                                        'https://pay.google.com',
                                                        'https://pay.sandbox.google.com',
                                                        'https://payments.google.com'
                                                    ];
                                                    
                                                    if (trustedDomains.indexOf(parsedUrl.origin) !== -1) {
                                                        window.location.href = redirectUrl;
                                                    } else if (redirectUrl.indexOf('://') === -1 && redirectUrl.indexOf('//') !== 0) {
                                                        // Allow relative URLs
                                                        window.location.href = redirectUrl;
                                                    } else {
                                                        console.error('Blocked redirect to untrusted URL:', redirectUrl);
                                                        reject({ error: 'INVALID_REDIRECT' });
                                                    }
                                                } catch (e) {
                                                    // Fallback for relative URLs
                                                    if (redirectUrl.indexOf('://') === -1 && redirectUrl.indexOf('//') !== 0) {
                                                        window.location.href = redirectUrl;
                                                    } else {
                                                        console.error('Invalid URL format:', redirectUrl);
                                                        reject({ error: 'INVALID_REDIRECT' });
                                                    }
                                                }
                                                
                                                resolve({
                                                    transactionState: 'SUCCESS'
                                                });
                                                return true;
                                            } else if (response['form_data']) {
                                                resolve({
                                                    transactionState: 'SUCCESS'
                                                });
                                                return true;
                                            } else if (response['3dsframe'] && response['data']) {
                                                var ThreeDSURL = url.build('lloyds/paymentjs/threedsframe');
                                                var redirectUrl = ThreeDSURL + '?ipg_id=' + encodeURIComponent(response['ipg_transaction_id']) + 
                                                    '&order_id=' + encodeURIComponent(response['order_id']) + 
                                                    '&threeds_data=' + encodeURIComponent(response['data']);
                                                window.location.href = redirectUrl;
                                                
                                                resolve({
                                                    transactionState: 'SUCCESS'
                                                });
                                            } else {
                                                globalMessageList.addErrorMessage({
                                                    message: response.message || $t('Payment processing failed.')
                                                });
                                                
                                                resolve({
                                                    transactionState: 'ERROR',
                                                    error: {
                                                        reason: 'PAYMENT_DATA_INVALID',
                                                        message: 'Payment processing failed.',
                                                        intent: 'PAYMENT_AUTHORIZATION'
                                                    }
                                                });
                                                location.reload();
                                                return false;
                                            }
                                        } else {
                                            globalMessageList.addErrorMessage({
                                                message: $t('Payment processing failed.')
                                            });
                                            
                                            resolve({
                                                transactionState: 'ERROR',
                                                error: {
                                                    reason: 'PAYMENT_DATA_INVALID',
                                                    message: 'Payment processing failed.',
                                                    intent: 'PAYMENT_AUTHORIZATION'
                                                }
                                            });
                                            location.reload();
                                            return false;
                                        }
                                    }).fail(function (response) {
                                        console.error('Payment processing error:', response);
                                        fullScreenLoader.stopLoader();
                                        
                                        globalMessageList.addErrorMessage({
                                            message: $t('Payment processing failed. Please try again.')
                                        });
                                        
                                        resolve({
                                            transactionState: 'ERROR',
                                            error: {
                                                reason: 'PAYMENT_DATA_INVALID',
                                                message: 'Payment processing failed.',
                                                intent: 'PAYMENT_AUTHORIZATION'
                                            }
                                        });
                                    });
                                } else {
                                    fullScreenLoader.stopLoader();
                                    globalMessageList.addErrorMessage({
                                        message: data.message || $t('Unable to process order.')
                                    });

                                    resolve({
                                        transactionState: 'ERROR',
                                        error: {
                                            reason: 'PAYMENT_DATA_INVALID',
                                            message: 'Order creation failed.',
                                            intent: 'PAYMENT_AUTHORIZATION'
                                        }
                                    });
                                }
                            },
                            error: function(err) {
                                console.error('Order creation error:', err);
                                fullScreenLoader.stopLoader();

                                resolve({
                                    transactionState: 'ERROR',
                                    error: {
                                        reason: 'PAYMENT_DATA_INVALID',
                                        message: 'Unable to create order.',
                                        intent: 'PAYMENT_AUTHORIZATION'
                                    }
                                });
                            }
                        });
                    });
                },

                addGooglePayButton: function () {
                    var gpSelf = this;
                    var paymentsClient = this.getGooglePaymentsClient();

                    if (!paymentsClient) {
                        console.log('Google Pay client not available');
                        return;
                    }
                    var isReadyToPayRequest = {
                        apiVersion: this.apiVersion,
                        apiVersionMinor: this.apiVersionMinor,
                        allowedPaymentMethods: [gpSelf.getBaseCardPaymentMethod()],
                        existingPaymentMethodRequired: false
                    };

                    paymentsClient.isReadyToPay(isReadyToPayRequest)
                        .then(function (response) {
                            if (response.result) {
                                var buttonOptions = {
                                    onClick: gpSelf.onGooglePaymentButtonClicked.bind(gpSelf),
                                    buttonColor: gpSelf.buttonColor,
                                    buttonType: gpSelf.buttonType
                                };
                                var button = paymentsClient.createButton(buttonOptions);
                                var $container = $(gpSelf.buttonSelector);
                                $container.empty();
                                if (button && button instanceof Element) {
                                    $container[0].appendChild(button);
                                }
                            } else {
                                var $container = $(gpSelf.buttonSelector);
                                $container.empty();
                                var messageDiv = document.createElement('div');
                                messageDiv.className = 'message info';
                                var messageSpan = document.createElement('span');
                                messageSpan.textContent = $.mage.__('Google Pay is not available for this device or browser') || 'Google Pay is not available';
                                messageDiv.appendChild(messageSpan);
                                $container[0].appendChild(messageDiv);
                            }
                        }).catch(function (err) {
                            console.log('Google Pay initialization error:', err);
                            var $container = $(gpSelf.buttonSelector);
                            $container.empty();
                            var messageDiv = document.createElement('div');
                            messageDiv.className = 'message error';
                            var messageSpan = document.createElement('span');
                            messageSpan.textContent = $.mage.__('Unable to initialize Google Pay') || 'Unable to initialize Google Pay';
                            messageDiv.appendChild(messageSpan);
                            $container[0].appendChild(messageDiv);
                        });
                },

                isValidRedirectUrl: function(url) {
                    if (!url || typeof url !== 'string') {
                        return false;
                    }
                    
                    try {
                        // Parse the URL
                        var parsedUrl = new URL(url, window.location.origin);
                        
                        // Allow only relative URLs or same-origin URLs
                        if (parsedUrl.origin === window.location.origin) {
                            return true;
                        }
                        
                        // Allow specific trusted payment domains
                        var trustedDomains = [
                            'https://pay.google.com',
                            'https://pay.sandbox.google.com',
                            'https://pay.google.co.uk',
                            'https://pay.google.ie',
                            'https://payments.google.com',
                            'https://applepay.cdn-apple.com',
                            'https://apple-pay-gateway.apple.com',
                            'https://apple-pay-gateway-cert.apple.com',
                            'https://apple-pay-gateway-nc-pod1.apple.com',
                            'https://apple-pay-gateway-pr-pod1.apple.com'
                        ];
                        
                        return trustedDomains.some(function(domain) {
                            return parsedUrl.href.startsWith(domain);
                        });
                    } catch (e) {
                        // If URL parsing fails, treat as relative URL
                        // But ensure it doesn't contain protocol separator
                        return url.indexOf('://') === -1 && url.indexOf('//') !== 0;
                    }
                },
                
                getBaseCardPaymentMethod: function () {
                    return {
                        type: 'CARD',
                        parameters: {
                            allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                            allowedCardNetworks: this.supported_networks,
                            assuranceDetailsRequired: true
                        }
                    };
                },

                getCardPaymentMethod: function () {
                    var baseCardPaymentMethod = this.getBaseCardPaymentMethod();
                    baseCardPaymentMethod.tokenizationSpecification = {
                        type: 'PAYMENT_GATEWAY',
                        parameters: {
                            'gateway': 'fiservipg',
                            'gatewayMerchantId': this.gatewayMerchantId
                        }
                    };
                    baseCardPaymentMethod.parameters.billingAddressRequired = true;
                    baseCardPaymentMethod.parameters.billingAddressParameters = {
                        format: 'FULL',
                        phoneNumberRequired: true
                    };
                    return baseCardPaymentMethod;
                },

                getGoogleTransactionInfo: function (address) {
                    var self = this;

                    const payload = {
                        address: {
                            country_id: address.countryCode.toUpperCase(),
                            postcode: address.postcode,
                            region: address.administrativeArea
                        }
                    };

                    this.shippingAddress = payload.address;

                    return new Promise(function (resolve, reject) {
                        $.ajax({
                            url: window.baseUrl + 'lloyds/wallet/shippingoptions',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(payload),
                            success: function (result) {
                                if (!result) {
                                    return resolve({});
                                }

                                let virtualFlag = false;
                                const shippingMethods = [];
                                self.shippingMethods = {};

                                const productItems = customerData.get('cart')()?.items || [];
                                virtualFlag = productItems.every(item => item.is_virtual || item.product_type === 'bundle');

                                if (result.length === 0 && !virtualFlag) {
                                    alert($t('There are no shipping methods available for you right now.'));
                                    $('body').trigger('processStop');
                                    return resolve({});
                                }

                                const excludedCarriers = ['instore', 'instorepickup', 'in_store_pickup'];
                                for (const methodData of result) {
                                    if (typeof methodData.method_code !== 'string') continue;
                                    if (excludedCarriers.indexOf(methodData.carrier_code) !== -1) continue;

                                    const method = {
                                        identifier: methodData.method_code,
                                        label: stripHtml(methodData.method_title),
                                        detail: stripHtml(methodData.carrier_title) || '',
                                        amount: parseFloat(methodData.amount).toFixed(2)
                                    };

                                    shippingMethods.push(method);
                                    self.shippingMethods[methodData.method_code] = methodData;

                                    if (!self.shippingMethod) {
                                        self.shippingMethod = methodData.method_code;
                                        self.selectedShippingMethod = methodData.method_code;
                                    }
                                }

                                const selectedMethod = self.shippingMethods[self.selectedShippingMethod] || 
                                                       self.shippingMethods[shippingMethods[0]?.identifier];

                                const totalsPayload = {
                                    addressInformation: {
                                        address: {
                                            countryId: self.shippingAddress.country_id,
                                            region: self.shippingAddress.region,
                                            regionId: self.getRegionId(self.shippingAddress.country_id, self.shippingAddress.region),
                                            postcode: self.shippingAddress.postcode
                                        },
                                        shipping_method_code: virtualFlag ? null : selectedMethod?.method_code,
                                        shipping_carrier_code: virtualFlag ? null : selectedMethod?.carrier_code
                                    }
                                };

                                $.ajax({
                                    url: window.baseUrl + 'lloyds/wallet/shippingupdate',
                                    type: 'POST',
                                    contentType: 'application/json',
                                    data: JSON.stringify(totalsPayload),
                                    success: function (totals) {
                                        self.setGrandTotalAmount(totals.base_grand_total);

                                        resolve({
                                            currencyCode: self.currentCurrency ?? 'GBP',
                                            totalPriceStatus: 'FINAL',
                                            totalPrice: self.getGrandTotalAmount(),
                                            totalPriceLabel: $.mage.__('Total') || 'Total'
                                        });
                                    },
                                    error: function (error) {
                                        console.error('Error updating shipping:', error);
                                        $('body').trigger('processStop');
                                        resolve({});
                                    }
                                });
                            },
                            error: function (error) {
                                console.error('Error fetching shipping options:', error);
                                $('body').trigger('processStop');
                                resolve({});
                            }
                        });
                    });
                },
                
                getGooglePaymentDataRequest: function () {
                    var address = {
                        countryCode: this.countryCode,
                        administrativeArea: "",
                        postcode: ""
                    };
                    var request = {
                        apiVersion: this.apiVersion,
                        apiVersionMinor: this.apiVersionMinor,
                        allowedPaymentMethods: [this.getCardPaymentMethod()],
                        transactionInfo: {
                            currencyCode: this.currentCurrency ?? 'GBP',
                            totalPriceStatus: 'FINAL',
                            totalPrice: this.getGrandTotalAmount(),
                            totalPriceLabel: $.mage.__('Total') || 'Total'
                        },
                        merchantInfo: {
                            merchantName: this.merchantName
                        },
                        emailRequired: true
                    };

                    if (this.merchantId && this.merchantId.trim() !== '') {
                        request.merchantInfo.merchantId = this.merchantId;
                    }

                    request.callbackIntents = [
                        'SHIPPING_ADDRESS',
                        'SHIPPING_OPTION',
                        'PAYMENT_AUTHORIZATION'
                    ];

                    request.shippingAddressRequired = true;
                    request.shippingAddressParameters = {
                        phoneNumberRequired: true,
                        allowedCountryCodes: this.allowedCountries
                    };

                    request.shippingOptionRequired = true;
                    return request;
                },

                onGooglePaymentButtonClicked: function () {
                    var gpSelf = this;
                    var cartData = customerData.get('cart')();
                    if (!cartData || !cartData.summary_count || cartData.summary_count === 0) {
                        console.log('Your cart is empty');
                        return;
                    }
                    $('body').trigger('processStart');
                    gpSelf.paymentsClient = null;
                    var paymentsClient = gpSelf.getGooglePaymentsClient();
                    var paymentDataRequest = gpSelf.getGooglePaymentDataRequest();
                    paymentsClient.loadPaymentData(paymentDataRequest)
                        .then(function (paymentData) {
                            return Promise.resolve(true);
                        })
                        .catch(function (error) {
                            $('body').trigger('processStop');

                            gpSelf.paymentsClient = null;

                            if (error.statusCode === "CANCELED") {
                                console.log('Payment was canceled by user');
                                location.reload();
                            } else if (error.name === 'AbortError') {
                                console.log('Browser process disconnected during payment');
                                alert($.mage.__('The payment process was interrupted. Please try again.'));
                            } else {
                                console.log('Google Pay error:', error);
                                alert($.mage.__('There was an issue with Google Pay. Please try again or use a different payment method.'));
                            }
                        });
                },

                onPaymentDataChanged: function (intermediatePaymentData) {
                    var self = this;
                    return new Promise(async function (resolve) {
                        try {
                            if (intermediatePaymentData.callbackTrigger === 'INITIALIZE' ||
                                intermediatePaymentData.callbackTrigger === 'SHIPPING_ADDRESS') {

                                const shippingAddress = intermediatePaymentData.shippingAddress;

                                const address = {
                                    countryCode: (shippingAddress.countryCode || 'GB').toUpperCase(),
                                    administrativeArea: shippingAddress.administrativeArea || '',
                                    postcode: shippingAddress.postalCode || ''
                                };

                                const payload = {
                                    address: {
                                        country_id: address.countryCode,
                                        region: address.administrativeArea,
                                        postcode: address.postcode
                                    }
                                };

                                $.ajax({
                                    url: window.baseUrl + 'lloyds/wallet/shippingoptions',
                                    type: 'POST',
                                    contentType: 'application/json',
                                    data: JSON.stringify(payload),
                                    success: async function (shippingMethods) {
                                        const shippingOptions = self.getShippingOptions(shippingMethods);
                                        if (shippingOptions.length > 0) {
                                            let defaultOptionId = self.selectedShippingMethod || shippingOptions[0].id;
                                            if (!shippingOptions.some(opt => opt.id === defaultOptionId)) {
                                                defaultOptionId = shippingOptions[0].id;
                                                self.selectedShippingMethod = defaultOptionId;
                                            }

                                            const newTransactionInfo = await self.getGoogleTransactionInfo(address);

                                            resolve({
                                                newShippingOptionParameters: {
                                                    defaultSelectedOptionId: defaultOptionId,
                                                    shippingOptions: shippingOptions
                                                },
                                                newTransactionInfo
                                            });
                                        } else {
                                            resolve({
                                                error: {
                                                    reason: 'SHIPPING_OPTION_INVALID',
                                                    message: $.mage.__('No shipping options available for this address'),
                                                    intent: 'SHIPPING_ADDRESS'
                                                }
                                            });
                                        }
                                    },
                                    error: function (error) {
                                        console.error('Error fetching shipping methods:', error);
                                        $('body').trigger('processStop');
                                        resolve({});
                                    }
                                });

                            } else if (intermediatePaymentData.callbackTrigger === 'SHIPPING_OPTION') {

                                const address = {
                                    countryCode: (self.shippingAddress.country_id || 'GB').toUpperCase(),
                                    administrativeArea: self.shippingAddress.region || '',
                                    postcode: self.shippingAddress.postcode || ''
                                };

                                self.selectedShippingMethod = intermediatePaymentData.shippingOptionData.id;

                                const newTransactionInfo = await self.getGoogleTransactionInfo(address);

                                resolve({ newTransactionInfo });
                            } else {
                                console.log('Unhandled callback trigger: ' + intermediatePaymentData.callbackTrigger);
                                resolve({});
                            }
                        } catch (e) {
                            console.error('Error in onPaymentDataChanged:', e);
                            resolve({});
                        }
                    });
                },

                getShippingOptions: function (result) {
                    var self = this;
                    if (!result) return;

                    let virtualFlag = false;
                    const shippingMethods = [];
                    self.shippingMethods = {};

                    if (result.length === 0) {
                        const productItems = customerData.get('cart')().items || [];

                        for (const item of productItems) {
                            if (item.is_virtual || item.product_type === 'bundle') {
                                virtualFlag = true;
                            } else {
                                virtualFlag = false;
                            }
                        }

                        if (!virtualFlag) {
                            alert($t('There are no shipping methods available for you right now. Please try again or use an alternative payment method.'));
                            $('body').trigger('processStop');
                            return;
                        }
                    }

                    const excludedCarriers = ['instore', 'instorepickup', 'in_store_pickup'];
                    for (const methodData of result) {
                        if (typeof methodData.method_code !== 'string') continue;
                        if (excludedCarriers.indexOf(methodData.carrier_code) !== -1) continue;

                        const shipLabel = stripHtml(methodData.method_title) + " - " + priceUtils.formatPrice(methodData.amount);
                        const shippingLabel = (methodData.amount) > 0 ? shipLabel : stripHtml(methodData.method_title);

                        const method = {
                            id: methodData.method_code,
                            label: shippingLabel,
                            description: stripHtml(methodData.carrier_title) || '',
                            cost: parseFloat(methodData.amount).toFixed(2)
                        };

                        shippingMethods.push(method);
                        self.shippingMethods[methodData.method_code] = methodData;

                        if (!self.shippingMethod) {
                            self.shippingMethod = methodData.method_code;
                            self.selectedShippingMethod = methodData.method_code;
                        }
                    }

                    return shippingMethods;
                },

                /**
                 *
                 * @param value
                 */
                setGrandTotalAmount: function (value) {
                    this.grandTotal = parseFloat(value).toFixed(2);
                },

                /**
                 *
                 * @returns {number}
                 */
                getGrandTotalAmount: function () {
                    return parseFloat(this.grandTotal).toFixed(2);
                },

                getRegionId: function (countryCode, regionCode) {
                    if (typeof regionCode !== 'string') {
                        return null;
                    }

                    regionCode = regionCode.toLowerCase().replace(/[^A-Z0-9]/ig, '');

                    if (typeof this.countryLists[countryCode] !== 'undefined'
                        && typeof this.countryLists[countryCode][regionCode] !== 'undefined') {
                        return this.countryLists[countryCode][regionCode];
                    }

                    return 0;
                }
            };
        }
    });
});
