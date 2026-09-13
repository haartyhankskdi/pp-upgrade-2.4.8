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

define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'mage/url',
        'mage/cookies',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/redirect-on-success',
        'AutifyDigital_LloydscardnetPayment/js/lcnetredirect/form-builder',
        'Magento_Checkout/js/model/error-processor',
        'AutifyDigital_LloydscardnetPayment/js/model/applepay-sdk'
    ],
    function (ko, $, Component, placeOrderAction, quote, $t, url, cookies, additionalValidators,
              fullScreenLoader, globalMessageList, redirectOnSuccessAction, formBuilder, errorProcessor,
              ensureApplePaySdk) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'AutifyDigital_LloydscardnetPayment/payment/cardnetapplepay',
                grandTotal: 0,
                currencyCode: 'GBP',
                isLoggedIn: false,
                storeCode: 'default',
                shippingAddress: {},
                countryDirectory: null,
                shippingMethods: {}
            },
            redirectAfterPlaceOrder: false,
            paymentInteration: window.checkoutConfig.payment.cardnetapplepay.payment_integration_type || 'onsite',

            applePaySupported: function () {
                if (location.protocol !== 'https:') {
                    console.warn('Apple Pay requires the use of HTTPS');
                    return false;
                }

                if ((window.ApplePaySession && window.ApplePaySession.canMakePayments()) !== true) {
                    console.warn('Apple Pay is not supported on this device/browser');
                    return false;
                }
                //display ApplePay

                return true;
            },

            paymentInterationType: function () {
                return this.paymentInteration;
            },

            getButtonStyle: function () {
                return window.checkoutConfig.payment.cardnetapplepay.button_color || 'black';
            },

            /**
             * Initializes component
             */
            initialize: function () {
                this._super();
                var thisObj = this;

                ensureApplePaySdk().then(function (sdkReady) {
                    thisObj.isApplePaySupported(sdkReady && thisObj.applePaySupported());
                });

                return this;
            },

            startApplePayCheckout: function () {
                var thisObj = this;

                if (
                    !(window.ApplePaySession &&
                    window.ApplePaySession.canMakePayments() &&
                    thisObj.validate() &&
                    additionalValidators.validate())
                ) {
                    return;
                }

                var paymentRequest = thisObj.getPaymentRequest();
                var applepaySession = new ApplePaySession(1, paymentRequest);

                applepaySession.onvalidatemerchant = function (event) {
                    var promise = thisObj.performValidation(event.validationURL);
                    promise.then(function (merchantSession) {
                        applepaySession.completeMerchantValidation(merchantSession);
                    });
                };

                applepaySession.onpaymentauthorized = function (event) {
                    thisObj.startPlaceOrder(applepaySession, event.payment.token);
                };

                applepaySession.oncancel = function () {
                    fullScreenLoader.stopLoader();
                };

                applepaySession.begin();
            },

            /**
             * Subscribe to grand totals
             */
            initObservable: function () {
                var self = this;
                this._super();

                this.isApplePaySupported = ko.observable(false);

                this.isBillingAddressRequired = ko.observable(true);
                this.isBillingAddressFilled = ko.observable(this.hasCompleteBillingAddress());

                quote.billingAddress.subscribe(function () {
                    self.isBillingAddressFilled(self.hasCompleteBillingAddress());
                });

                this.grandTotal = parseFloat(quote.totals()['base_grand_total']).toFixed(2);

                this.currencyCode = quote.totals()['quote_currency_code'];

                quote.totals.subscribe(function () {
                    if (self.grandTotal !== quote.totals()['base_grand_total']) {
                        self.grandTotal = parseFloat(quote.totals()['base_grand_total']).toFixed(2);
                    }
                });

                return this;
            },

            hasCompleteBillingAddress: function () {
                var billing = quote.billingAddress();
                if (!billing) {
                    return false;
                }
                var street = billing.street || [];
                if (!street[0]) {
                    return false;
                }
                if (!billing.city || !billing.postcode || !billing.countryId) {
                    return false;
                }
                if (!billing.firstname || !billing.lastname || !billing.telephone) {
                    return false;
                }
                return true;
            },

            getPaymentRequest: function () {
                var countryCode = quote.billingAddress().countryId;

                var getPaymentRequest = {
                    currencyCode: this.currencyCode,
                    countryCode: countryCode,
                    total: {
                        label: 'Total',
                        amount: this.grandTotal
                    },
                    merchantCapabilities: [ 'supports3DS', 'supportsCredit', 'supportsDebit' ],
                    supportedNetworks: (window.checkoutConfig.payment.cardnetapplepay.supported_networks).split(","),
                    requiredBillingContactFields: ['postalAddress', 'name']
                };
                return getPaymentRequest;
            },

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
             * API Urls for logged in / guest
             */
            getApiUrl: function (uri) {
                if (this.getIsLoggedIn() === true) {
                    return 'rest/default/V1/carts/mine/' + uri;
                }
                return 'rest/default/V1/guest-carts/' + this.getQuoteId() + '/' + uri;

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

                    // Format shipping methods array.
                    for (let i = 0; i < result.length; i++) {
                        if (typeof result[i].method_code !== 'string') {
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
                            window.ApplePaySession.STATUS_SUCCESS,
                            shippingMethods,
                            {
                                label: this.getDisplayName(),
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
                        console.error('Braintree ApplePay: Unable to get totals', error);
                        return false;
                    });

                }.bind(this)).fail(function (result) {
                    session.abort();
                    // eslint-disable-next-line
                    alert($t('We\'re unable to find any shipping methods for you. Please try an alternative payment method.'));
                    // eslint-disable-next-line
                    console.error('Braintree ApplePay: Unable to find shipping methods for estimate-shipping-methods', result);
                    return false;
                });
            },

            onShippingMethodSelect: function (event, session) {

            },
            /**
             * Applepay place order method
             */
            startPlaceOrder: function (session, token) {
                var self = this;
                var placeOrderFlag = self.placeOrder();

                var waitforMinTime = new Date().getTime();
                var waitforMinTimeEnd = waitforMinTime;
                while(waitforMinTimeEnd < waitforMinTime + 5000) {
                    waitforMinTimeEnd = new Date().getTime();
                }

                if(placeOrderFlag) {
                    var paymentUrl = url.build('lloyds/applepay/payment');
                    fullScreenLoader.startLoader();
                    $.ajax({
                        url: paymentUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            form_key: $.mage.cookies.get('form_key'),
                            paymentData: JSON.stringify(token.paymentData),
                        },
                        complete: function(response) {
                            let paymentResponse = response.responseJSON;
                            if(paymentResponse.status == 'success') {
                                session.completePayment(session.STATUS_SUCCESS);
                                redirectOnSuccessAction.execute();
                            } else {
                                globalMessageList.addErrorMessage({
                                    message: $t('Something went wrong with the payment. Please contact to customer support.')
                                });
                                session.abort();
                            }
                            fullScreenLoader.stopLoader();
                        },
                        error: function (xhr, status, errorThrown) {
                            session.completePayment(session.STATUS_FAILURE);
                            session.abort();
                            globalMessageList.addErrorMessage({
                                message: $t('Something went wrong with the payment. Please contact to customer support.')
                            });
                            fullScreenLoader.stopLoader();
                        }
                    });
                } else {
                    session.completePayment(session.STATUS_FAILURE);
                    globalMessageList.addErrorMessage({
                        message: $t('Something went wrong with the payment. Please contact to customer support.')
                    });
                    fullScreenLoader.stopLoader();

                }
            },

            /**
             *
             * @param data
             * @param event
             * @returns {boolean}
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() &&
                    additionalValidators.validate() &&
                    this.isPlaceOrderActionAllowed() === true
                ) {
                    this.isPlaceOrderActionAllowed(false);

                    if (this.paymentInteration === 'hosted') {
                        this.handleHostedPaymentRedirect();
                        return true;
                    }

                    this.getPlaceOrderDeferredObject()
                        .done(
                            function () {
                                return true;
                            }
                        ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    );

                    return true;
                }

                return false;
            },

            handleHostedPaymentRedirect: function () {
                var self = this;
                
                this.getPlaceOrderDeferredObject()
                    .done(function () {
                        self.afterPlaceOrder();
                    })
                    .always(function () {
                        self.isPlaceOrderActionAllowed(true);
                    });
            },

            afterPlaceOrder: function () {
                if (this.paymentInteration === 'hosted') {
                    fullScreenLoader.startLoader();
                    var self = this;
                    $.ajax({
                        showLoader: true,
                        url: url.build('lcnetpayment/index/redirectPostData'),
                        data: {},
                        type: 'POST'
                    }).done(function (response) {
                        if (response && !response.error) {
                            self.redirectAfterPlaceOrder = true;
                            formBuilder(response).submit();
                            return true;
                        } else {
                            errorProcessor.process(response, self.messageContainer);
                            fullScreenLoader.stopLoader();
                            return false;
                        }
                    }).fail(function (response) {
                        errorProcessor.process(response, self.messageContainer);
                        fullScreenLoader.stopLoader();
                    });
                    return false;
                }
            }
        });
    }
);
