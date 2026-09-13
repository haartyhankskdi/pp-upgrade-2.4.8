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

require([
    'jquery',
    'mage/translate',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/cookies',
    'Magento_Ui/js/model/messageList',
    'mage/storage',
    'AutifyDigital_LloydscardnetPayment/js/model/applepay-sdk'
], function ($, $t, url, customerData, fullScreenLoader, cookies, globalMessageList, storage, ensureApplePaySdk) {
    'use strict';
    var ApplePayProduct = {
        defaults: {
            grandTotal: 0,
            currencyCode: 'GBP',
            countryCode: 'GB',
            isPlaceOrderActionAllowed: true,
            countryLists: null,
            quoteId: null,
            shippingMethods: {},
            shippingAddress: {}
        },

        init: function () {

            let minOrderAmount = parseFloat(window.applePayConfig.minOrderAmount) || 0;
            let maxOrderAmount = parseFloat(window.applePayConfig.maxOrderAmount) || 999999;
            let cartTotal = this.defaults.grandTotal;

            if (cartTotal < minOrderAmount || cartTotal > maxOrderAmount) {
                return;
            }

            var self = this;
            ensureApplePaySdk().then(function (sdkReady) {
                if (sdkReady && self.applePaySupported()) {
                    self.createButton();
                    self.attachButtonEvents();
                }
            });

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

        applePaySupported: function () {
            if (location.protocol !== 'https:') {
                console.warn('Apple Pay requires the use of HTTPS');
                return false;
            }

            if ((window.ApplePaySession && window.ApplePaySession.canMakePayments()) !== true) {
                console.warn('Apple Pay is not supported on this device/browser');
                return false;
            }

            return true;
        },

        createButton: function () {
            let buttonColor = window.applePayConfig.button_color || 'black';
            let applePayButton = '<apple-pay-button id="cardnet-applepay-minicart-el" class="cardnet-apple-pay-button" buttonstyle="' + buttonColor + '" type="pay"></apple-pay-button>';
            $('#cardnet-applepay-minicart-btn').html(applePayButton).show();
        },

        attachButtonEvents: function () {
            let self = this;

            var button = document.getElementById('cardnet-applepay-minicart-el');
            if (!button) {
                return;
            }
            button.onclick = function () {
                if (window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
                    $('body').trigger('processStart');
                    let paymentRequest = self.getPaymentRequest();
                    let applepaySession = new ApplePaySession(1, paymentRequest);

                    applepaySession.onvalidatemerchant = function (event) {
                        let promise = self.performValidation(event.validationURL);
                        promise.then(function (merchantSession) {
                            applepaySession.completeMerchantValidation(merchantSession);
                        });
                    };

                    applepaySession.oncancel = function(event) {
                        $('body').trigger('processStop');
                    };

                    applepaySession.onpaymentauthorized = function (event) {
                        self.startPlaceOrder(applepaySession, event);
                    };

                    applepaySession.onshippingcontactselected = function (event) {
                        return self.onShippingContactSelect(event, applepaySession);
                    };

                    // Attach onShippingMethodSelect method
                    applepaySession.onshippingmethodselected = function (event) {
                        return self.onShippingMethodSelect(event, applepaySession);
                    };

                    applepaySession.begin();
                }
            };
        },

        onShippingContactSelect: function (event, session) {
            const self = this;
            const address = event.shippingContact;
            const payload = {
                address: {
                    region: address.administrativeArea,
                    country_id: address.countryCode.toUpperCase(),
                    postcode: address.postalCode
                }
            };

            this.shippingAddress = payload.address;

            function handleShippingOptions(result) {
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
                        session.abort();
                        alert($t('There are no shipping methods available for you right now. Please try again or use an alternative payment method.'));
                        $('body').trigger('processStop');
                        return;
                    }
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
                    }
                }

                const firstMethod = shippingMethods[0];
                const selectedMethod = self.shippingMethods[firstMethod.identifier];

                const totalsPayload = {
                    addressInformation: {
                        address: {
                            countryId: self.shippingAddress.country_id,
                            region: self.shippingAddress.region,
                            regionId: self.getRegionId(self.shippingAddress.country_id, self.shippingAddress.region),
                            postcode: self.shippingAddress.postcode
                        },
                        shipping_method_code: virtualFlag ? null : selectedMethod.method_code,
                        shipping_carrier_code: virtualFlag ? null : selectedMethod.carrier_code
                    }
                };

                //Call controller to update the shipping method
                $.ajax({
                    url: window.baseUrl + 'lloyds/wallet/shippingupdate',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(totalsPayload),
                    success: function(totals) {
                        self.setGrandTotalAmount(totals.base_grand_total);

                        // Pass shipping methods back
                        session.completeShippingContactSelection(
                            session.STATUS_SUCCESS,
                            shippingMethods,
                            {
                                label: 'Total',
                                amount: self.getGrandTotalAmount()
                            },
                            [{
                                type: 'final',
                                label: $t('Shipping'),
                                amount: virtualFlag ? 0 : shippingMethods[0].amount
                            }]
                        );
                    },
                    error: function (error) {
                        console.error('Error calling shipping controller:', error);
                        $('body').trigger('processStop');
                    }
                });
            }

            function fetchShippingOptions() {
                $.ajax({
                    url: window.baseUrl + 'lloyds/wallet/shippingoptions',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: handleShippingOptions,
                    error: function (error) {
                        console.error('Error calling shipping controller:', error);
                        $('body').trigger('processStop');
                    }
                });
            }

            const cartData = customerData.get('cart')();
            const productItems = cartData?.items || [];

            if (productItems.length > 0) {
                fetchShippingOptions();
            } else {
                setTimeout(() => {
                    const updatedItems = customerData.get('cart')()?.items || [];
                    if (updatedItems.length > 0) {
                        fetchShippingOptions();
                    }
                }, 2000);
            }
        },

        onShippingMethodSelect: function (event, session) {
            var self = this;
            let shippingMethod = event.shippingMethod,
                payload = {
                    'addressInformation': {
                        'address': {
                            'countryId': this.shippingAddress.country_id,
                            'region': this.shippingAddress.region,
                            'regionId': this.getRegionId(this.shippingAddress.country_id,
                                this.shippingAddress.region),
                            'postcode': this.shippingAddress.postcode
                        },
                        'shipping_method_code': this.shippingMethods[shippingMethod.identifier].method_code,
                        'shipping_carrier_code': this.shippingMethods[shippingMethod.identifier].carrier_code
                    }
                };

            this.shippingMethod = shippingMethod.identifier;

            $.ajax({
                url: window.baseUrl + 'lloyds/wallet/shippingupdate',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(totals) {

                    self.setGrandTotalAmount(totals.base_grand_total);

                    // Pass shipping methods back
                    session.completeShippingMethodSelection(
                        session.STATUS_SUCCESS,
                        {
                            label: 'Total',
                            amount: self.getGrandTotalAmount()
                        },
                        [{
                            type: 'final',
                            label: $t('Shipping'),
                            amount: shippingMethod.amount
                        }]
                    );
                },
                error: function (error) {
                    console.error('Error calling shipping controller:', error);
                    $('body').trigger('processStop');
                }
            });
        },

        /**
         * Start the place order process
         */
        startPlaceOrder: function (session, event) {
            var self = this;

            let shippingContact = event.payment.shippingContact,
                billingContact = event.payment.billingContact;

            var formKey = $.mage.cookies.get('form_key');

            var orderShippingMethodCarrierCode = this.shippingMethods[this.shippingMethod].carrier_code;
            var orderShippingMethodMethodCode = this.shippingMethods[this.shippingMethod].method_code;

            $.ajax({
                url: url.build('lloyds/wallet/applepaycreateorder'),
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    form_key: formKey,
                    shippingContact: shippingContact,
                    billingContact: billingContact,
                    shipping_method: orderShippingMethodCarrierCode + '_' + orderShippingMethodMethodCode,
                    payment_method: 'cardnetapplepay'
                }),
                success: function(data) {
                    if (data.success) {
                        $.ajax({
                            url: url.build('lloyds/applepay/payment'),
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                form_key: formKey,
                                paymentData: JSON.stringify(event.payment.token.paymentData)
                            },
                            success: function(response) {
                                fullScreenLoader.stopLoader();
                                $('body').trigger('processStop');
                                if (response && response.status === 'success') {
                                    session.completePayment(session.STATUS_SUCCESS);
                                    customerData.invalidate(['cart']);
                                    window.location.href = '/checkout/onepage/success';
                                } else {
                                    globalMessageList.addErrorMessage({
                                        message: $t('Something went wrong with the payment. Please contact customer support.')
                                    });
                                    session.completePayment(session.STATUS_FAILURE);
                                }
                            },
                            error: function(err) {
                                console.error('Payment error:', err);
                                session.abort();
                                fullScreenLoader.stopLoader();
                            }
                        });
                    } else {
                        session.abort();
                        fullScreenLoader.stopLoader();
                        globalMessageList.addErrorMessage({
                            message: data.message || $t('Unable to process order.')
                        });
                    }
                },
                error: function(err) {
                    console.error('Order creation error:', err);
                    session.abort();
                    fullScreenLoader.stopLoader();
                }
            });
        },

        /**
         *
         * @param value
         */
        setGrandTotalAmount: function (value) {
            this.defaults.grandTotal = parseFloat(value).toFixed(2);
        },

        /**
         *
         * @returns {number}
         */
        getGrandTotalAmount: function () {
            return parseFloat(this.defaults.grandTotal);
        },

        getPaymentRequest: function () {
            return {
                currencyCode: this.defaults.currencyCode,
                countryCode: this.defaults.countryCode,
                total: {
                    label: 'Total',
                    amount: this.defaults.grandTotal
                },
                requiredShippingContactFields: ["postalAddress", "email", "phone"],
                requiredBillingContactFields: ["postalAddress", "email", "phone"],
                merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit'],
                supportedNetworks: window.applePayConfig.supported_networks.split(","),
            };
        },

        performValidation: function (validationURL) {
            let validationUrl = url.build('lloyds/applepay/index');
            return new Promise(function(resolve, reject) {
                let xhr = new XMLHttpRequest();
                xhr.onload = function() {
                    let data = JSON.parse(this.responseText);
                    resolve(data);
                };
                xhr.onerror = reject;
                xhr.open('GET', validationUrl + '?u=' + validationURL);
                xhr.send();
            });
        },

        /**
         *
         * @param countryCode
         * @param regionCode
         * @returns {number|*|null}
         */
        getRegionId: function(countryCode, regionCode) {
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

    $(document).ready(function () {
        ApplePayProduct.init();
    });
    return ApplePayProduct;
});
