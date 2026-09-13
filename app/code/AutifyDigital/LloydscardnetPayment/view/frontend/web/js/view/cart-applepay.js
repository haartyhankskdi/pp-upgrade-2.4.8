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
        'mage/cookies',
        'Magento_Ui/js/model/messageList',
        'mage/storage',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success',
        'AutifyDigital_LloydscardnetPayment/js/model/applepay-sdk'
    ],
    function ($, $t, url, cookies, globalMessageList, storage, customerData, quote, additionalValidators, createBillingAddress, createShippingAddress, selectShippingMethod, placeOrderAction, paymentService, fullScreenLoader, redirectOnSuccessAction, ensureApplePaySdk) {
        'use strict';

        var applePayModule = {
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

            /**
             * Initialize Apple Pay on cart page
             */
            init: function (config) {
                this.defaults.grandTotal = config.grandTotal;
                this.defaults.currencyCode = config.currencyCode;
                this.defaults.countryCode = config.countryCode;
                this.defaults.quoteId = config.quoteId;

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
                                name = region.name.toLowerCase().replace(/[^A-Z0-9]/ig, '');
                                this.countryLists[data.two_letter_abbreviation][name] = region.id;
                            }
                        }
                    }.bind(this));
                }
            },

            /**
             * Check if Apple Pay is supported in this phpowser/device
             */
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

            /**
             * Create Apple Pay button
             */
            createButton: function () {
                let buttonColor = window.checkoutConfig.payment.cardnetapplepay.button_color || 'black';
                let applePayButton = '<apple-pay-button id="cardnet-applepay-cart-btn" class="cardnet-apple-pay-button" buttonstyle="' + buttonColor + '" type="pay"></apple-pay-button>';
                $('#apple-pay-button-container').html(applePayButton);
            },

            /**
             * Attach events to the Apple Pay button
             */
            attachButtonEvents: function () {
                var self = this;

                var button = document.getElementById('cardnet-applepay-cart-btn');
                if (!button) {
                    return;
                }
                button.onclick = function () {
                    if (window.ApplePaySession && window.ApplePaySession.canMakePayments()) {
                        var paymentRequest = self.getPaymentRequest();
                        let applepaySession = new ApplePaySession(1, paymentRequest);

                        applepaySession.onvalidatemerchant = function (event) {
                            var promise = self.performValidation(event.validationURL);
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

                        // Attach onShippingContactSelect method
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

            getApiUrl: function (uri) {
                if (!!customerData.get('customer')().firstname) {
                    return 'rest/' + (window.checkoutConfig.storeCode || 'default') + '/V1/carts/mine/' + uri;
                }
                return 'rest/' + (window.checkoutConfig.storeCode || 'default') + '/V1/guest-carts/' + quote.getQuoteId() + '/' + uri;
            },

            onShippingContactSelect: function (event, session) {

                let address = event.shippingContact,
                    // Create a payload.
                    payload = {
                        address: {
                            city: address.locality,
                            region: address.administrativeArea,
                            country_id: address.countryCode.toUpperCase(),
                            postcode: address.postalCode,
                            save_in_address_book: 0
                        }
                    };

                this.shippingAddress = payload.address;

                // POST to endpoint for shipping methods.
                storage.post(
                    this.getApiUrl('estimate-shipping-methods'),
                    JSON.stringify(payload)
                ).done(function (result) {
                    // Stop if no shipping methods.
                    let virtualFlag = false,
                        shippingMethods = [],
                        totalsPayload = {};

                    if (result.length === 0) {
                        let productItems = customerData.get('cart')().items;

                        _.each(productItems,
                            function (item) {
                                if (item.is_virtual || item.product_type === 'bundle') {
                                    virtualFlag = true;
                                } else {
                                    virtualFlag = false;
                                }
                            }
                        );
                        if (!virtualFlag) {
                            session.abort();
                            // eslint-disable-next-line
                            alert($t('There are no shipping methods available for you right now. Please try again or use an alternative payment method.'));
                            return false;
                        }
                    }

                    this.shippingMethods = {};
                    var excludedCarriers = ['instore', 'instorepickup', 'in_store_pickup'];

                    // Format shipping methods array.
                    for (let i = 0; i < result.length; i++) {
                        if (typeof result[i].method_code !== 'string') {
                            continue;
                        }
                        if (excludedCarriers.indexOf(result[i].carrier_code) !== -1) {
                            continue;
                        }

                        let method = {
                            identifier: result[i].method_code,
                            label: stripHtml(result[i].method_title),
                            detail: result[i].carrier_title ? stripHtml(result[i].carrier_title) : '',
                            amount: parseFloat(result[i].amount).toFixed(2)
                        };

                        // Add method object to array.
                        shippingMethods.push(method);

                        this.shippingMethods[result[i].method_code] = result[i];

                        if (!this.shippingMethod) {
                            this.shippingMethod = result[i].method_code;
                        }
                    }

                    // Create payload to get totals
                    totalsPayload = {
                        'addressInformation': {
                            'address': {
                                'countryId': this.shippingAddress.country_id,
                                'region': this.shippingAddress.region,
                                'regionId': this.getRegionId(
                                    this.shippingAddress.country_id, this.shippingAddress.region),
                                'postcode': this.shippingAddress.postcode
                            },
                            'shipping_method_code': virtualFlag
                                ? null : this.shippingMethods[shippingMethods[0].identifier].method_code,
                            'shipping_carrier_code': virtualFlag
                                ? null : this.shippingMethods[shippingMethods[0].identifier].carrier_code
                        }
                    };

                    // POST to endpoint to get totals, using 1st shipping method
                    storage.post(
                        this.getApiUrl('totals-information'),
                        JSON.stringify(totalsPayload)
                    ).done(function (totals) {
                        // Set total
                        this.setGrandTotalAmount(totals.base_grand_total);

                        // Pass shipping methods back
                        session.completeShippingContactSelection(
                            session.STATUS_SUCCESS,
                            shippingMethods,
                            {
                                label: 'Total',
                                amount: this.getGrandTotalAmount()
                            },
                            [{
                                type: 'final',
                                label: $t('Shipping'),
                                amount: virtualFlag ? 0 : shippingMethods[0].amount
                            }]
                        );
                    }.bind(this)).fail(function (error) {
                        session.abort();
                        // eslint-disable-next-line
                        alert($t('We\'re unable to fetch the cart totals for you. Please try an alternative payment method.'));
                        return false;
                    });

                }.bind(this)).fail(function (result) {
                    session.abort();
                    // eslint-disable-next-line
                    alert($t('We\'re unable to find any shipping methods for you. Please try an alternative payment method.'));
                    // eslint-disable-next-line
                    return false;
                });
            },

            onShippingMethodSelect: function (event, session) {
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

                storage.post(
                    this.getApiUrl('totals-information'),
                    JSON.stringify(payload)
                ).done(function (r) {
                    this.setGrandTotalAmount(r.base_grand_total);

                    session.completeShippingMethodSelection(
                        session.STATUS_SUCCESS,
                        {
                            label: 'Total',
                            amount: this.getGrandTotalAmount()
                        },
                        [{
                            type: 'final',
                            label: $t('Shipping'),
                            amount: shippingMethod.amount
                        }]
                    );
                }.bind(this));
            },

            /**
             * Get payment request object for Apple Pay
             */
            getPaymentRequest: function () {
                var getPaymentRequest = {
                    currencyCode: this.defaults.currencyCode,
                    countryCode: this.defaults.countryCode,
                    total: {
                        label: 'Total',
                        amount: this.defaults.grandTotal
                    },
                    requiredShippingContactFields: ["postalAddress", "email", "phone"],
                    requiredBillingContactFields: ["postalAddress", "email", "phone"],
                    merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit'],
                    supportedNetworks: (window.checkoutConfig.payment.cardnetapplepay.supported_networks).split(","),
                };
                return getPaymentRequest;
            },

            /**
             * Perform merchant validation
             */
            performValidation: function (validationURL) {
                var validationUrl = url.build('lloyds/applepay/index');
                return new Promise(function(resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.onload = function() {
                        var data = JSON.parse(this.responseText);
                        resolve(data);
                    };
                    xhr.onerror = reject;
                    xhr.open('GET', validationUrl + '?u=' + validationURL);
                    xhr.send();
                });
            },

            /**
             * Start the place order process
             */
            startPlaceOrder: function (session, event) {
                var self = this;
                var placeOrderFlag = this.placeOrder(event, session);

                var waitforMinTime = new Date().getTime();
                var waitforMinTimeEnd = waitforMinTime;
                while(waitforMinTimeEnd < waitforMinTime + 5000) {
                    waitforMinTimeEnd = new Date().getTime();
                }

                if (!placeOrderFlag) {
                    //session.completePayment(session.STATUS_FAILURE);
                    globalMessageList.addErrorMessage({
                        message: $t('Something went wrong with the order. Please contact customer support.')
                    });
                    fullScreenLoader.stopLoader();
                }
            },

            /**
             * Place the order
             */
            placeOrder: function (event, session) {
                var self = this;


                fullScreenLoader.startLoader();

                let shippingContact = event.payment.shippingContact,
                    billingContact = event.payment.billingContact,
                    payload = {
                        'addressInformation': {
                            'shipping_address': {
                                'email': shippingContact.emailAddress,
                                'telephone': self.removeNonDigitCharacters(_.get(shippingContact, 'phoneNumber', '')),
                                'firstname': shippingContact.givenName,
                                'lastname': shippingContact.familyName,
                                'street': shippingContact.addressLines,
                                'city': shippingContact.locality,
                                'region': shippingContact.administrativeArea,
                                'region_id': self.getRegionId(
                                    shippingContact.countryCode.toUpperCase(), shippingContact.administrativeArea),
                                'region_code': null,
                                'country_id': shippingContact.countryCode.toUpperCase(),
                                'postcode': shippingContact.postalCode,
                                'same_as_billing': 0,
                                'customer_address_id': 0,
                                'save_in_address_book': 0
                            },
                            'billing_address': {
                                'email': shippingContact.emailAddress,
                                'telephone': self.removeNonDigitCharacters(_.get(shippingContact, 'phoneNumber', '')),
                                'firstname': billingContact.givenName,
                                'lastname': billingContact.familyName,
                                'street': billingContact.addressLines,
                                'city': billingContact.locality,
                                'region': billingContact.administrativeArea,
                                'region_id': self.getRegionId(
                                    billingContact.countryCode.toUpperCase(), billingContact.administrativeArea),
                                'region_code': null,
                                'country_id': billingContact.countryCode.toUpperCase(),
                                'postcode': billingContact.postalCode,
                                'same_as_billing': 0,
                                'customer_address_id': 0,
                                'save_in_address_book': 0
                            },
                            'shipping_method_code': this.shippingMethod
                                ? this.shippingMethods[this.shippingMethod].method_code : '' ,
                            'shipping_carrier_code': this.shippingMethod
                                ? this.shippingMethods[this.shippingMethod].carrier_code : ''
                        }
                    };

                // Set addresses
                storage.post(
                    this.getApiUrl('shipping-information'),
                    JSON.stringify(payload)
                ).done(function () {
                    // Submit payment information
                    let paymentInformation = {
                        'email': shippingContact.emailAddress,
                        'paymentMethod': {
                            'method': 'cardnetapplepay',
                            'additional_data': {
                                payment_method_nonce: JSON.stringify(event.payment.token.paymentData)
                            }
                        }
                    };


                    storage.post(
                        this.getApiUrl('payment-information'),
                        JSON.stringify(paymentInformation)
                    ).done(function () {
                        // session.completePayment(session.STATUS_SUCCESS);
                        // redirectOnSuccessAction.execute();
                        $.ajax({
                            url: url.build('lloyds/applepay/payment'),
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                form_key: $.mage.cookies.get('form_key'),
                                paymentData: JSON.stringify(event.payment.token.paymentData),
                                quoteId: self.defaults.quoteId
                            },
                            success: function(response) {
                                self.setIsPlaceOrderActionAllowed(true);
                                fullScreenLoader.stopLoader();
                                if(response && response.status == 'success') {
                                    session.completePayment(session.STATUS_SUCCESS);
                                    customerData.invalidate(['cart']);
                                    redirectOnSuccessAction.execute();
                                } else {
                                    globalMessageList.addErrorMessage({
                                        message: $t('Something went wrong with the payment. Please contact customer support.')
                                    });
                                    session.completePayment(session.STATUS_FAILURE);
                                    session.abort();
                                }
                            },
                            error: function (xhr, status, errorThrown) {
                                self.setIsPlaceOrderActionAllowed(true);
                                fullScreenLoader.stopLoader();
                                globalMessageList.addErrorMessage({
                                    message: $t('Something went wrong with the payment. Please contact customer support.')
                                });
                                session.completePayment(session.STATUS_FAILURE);
                                session.abort();
                            }
                        });
                    }.bind(this)).fail(function (r) {
                        self.setIsPlaceOrderActionAllowed(true);
                        fullScreenLoader.stopLoader();
                        session.completePayment(session.STATUS_FAILURE);
                        session.abort();
                        // eslint-disable-next-line
                        alert($t('We\'re unable to take your payment through Apple Pay. Please try an again or use an alternative payment method.'));
                        return false;
                    });

                }.bind(this)).fail(function (r) {
                    session.completePayment(session.STATUS_INVALID_BILLING_POSTAL_ADDRESS);
                });

                fullScreenLoader.stopLoader();
                return false;
            },

            /**
             * Check if place order action is allowed
             */
            isPlaceOrderActionAllowed: function() {
                return this.defaults.isPlaceOrderActionAllowed;
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
            },

            /**
             *
             * @param value
             * @returns {string}
             */
            removeNonDigitCharacters: function(value) {
                if (typeof value !== "string") {
                    return ""; // or maybe return value.toString().replace(/\D/g, "") if you want to coerce
                }
                return value.replace(/\D/g, "");
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

            /**
             * Set place order action allowed flag
             */
            setIsPlaceOrderActionAllowed: function(flag) {
                this.defaults.isPlaceOrderActionAllowed = flag;
            }
        };

        $(document).ready(function () {
            applePayModule.init({
                grandTotal: window.checkoutConfig.totalsData.grand_total,
                currencyCode: window.checkoutConfig.totalsData.quote_currency_code,
                countryCode: 'GB',
                quoteId: window.checkoutConfig.quoteId || null
            });
        });

        return applePayModule;
    });
