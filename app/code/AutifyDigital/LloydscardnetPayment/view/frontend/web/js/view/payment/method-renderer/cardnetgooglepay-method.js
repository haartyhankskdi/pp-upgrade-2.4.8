/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'mage/url',
        'mage/cookies',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/full-screen-loader',
        'AutifyDigital_LloydscardnetPayment/js/lcnetredirect/form-builder',
        'Magento_Checkout/js/model/error-processor'
    ],
    function (Component, $, ko, quote, $t, url, cookies, placeOrderAction, additionalValidators, redirectOnSuccessAction, messageList, fullScreenLoader, formBuilder, errorProcessor) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'AutifyDigital_LloydscardnetPayment/payment/cardnetgooglepay',
                googlePayButtonRendered: false
            },
            redirectAfterPlaceOrder: false,
            merchantId: window.checkoutConfig.payment.cardnetgooglepay.merchant_id,
            paymentMode: window.checkoutConfig.payment.cardnetgooglepay.payment_mode,
            merchantName: window.checkoutConfig.payment.cardnetgooglepay.merchant_name,
            currentCurrency: window.checkoutConfig.payment.cardnetgooglepay.currency,
            gatewayMerchantId: window.checkoutConfig.payment.cardnetgooglepay.gateway_merchant_id,
            allowedCards: (window.checkoutConfig.payment.cardnetgooglepay.supported_networks).split(","),
            buttonType: window.checkoutConfig.payment.cardnetgooglepay.button_type || 'buy',
            buttonColor: window.checkoutConfig.payment.cardnetgooglepay.button_color || 'black',
            paymentInteration: window.checkoutConfig.payment.cardnetgooglepay.payment_integration_type || 'onsite',
            grandTotal: 0,

            initialize: function () {
                var self = this;
                this._super();
                if (this.paymentInteration === 'onsite') {
                    window.googlePayInternval = setInterval(function () {
                        self.afterRenderGooglePayButton();
                    }, 1000);
                }
                this._super();
                return this;
            },

            paymentInterationType: function () {
                return this.paymentInteration;
            },

            initObservable: function () {
                var self = this;
                this._super();

                this.isBillingAddressRequired = ko.observable(true);
                this.isBillingAddressFilled = ko.observable(this.hasCompleteBillingAddress());

                quote.billingAddress.subscribe(function () {
                    self.isBillingAddressFilled(self.hasCompleteBillingAddress());
                });

                this.grandTotal = parseFloat(quote.totals()['base_grand_total']).toFixed(2);

                this.currentCurrency = quote.totals()['quote_currency_code'];

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

            afterRenderGooglePayButton: function () {
                if (!this.googlePayButtonRendered && document.getElementById('google-pay-button-container')) {
                    this.addGooglePayButton();
                    this.googlePayButtonRendered = true;
                    clearInterval(window.googlePayInternval);
                }
            },

            getBaseCardPaymentMethod: function () {
                const allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];
                return {
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: allowedCardAuthMethods,
                        allowedCardNetworks: this.allowedCards,
                        assuranceDetailsRequired: true
                    },
                    tokenizationSpecification: {
                        type: 'PAYMENT_GATEWAY',
                        parameters: {
                            gateway: 'fiservipg',
                            gatewayMerchantId: this.gatewayMerchantId
                        }
                    }
                };
            },

            addGooglePayButton: function () {
                const paymentsClient = this.getGooglePaymentsClient();
                paymentsClient.isReadyToPay({
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: [this.getBaseCardPaymentMethod()]
                }).then(response => {
                    if (response.result) {
                        const button = paymentsClient.createButton({
                            onClick: this.onGooglePaymentButtonClicked.bind(this),
                            buttonColor: this.buttonColor,
                            buttonType: this.buttonType
                        });

                        const container = document.getElementById('google-pay-button-container');
                        if (container) {
                            container.appendChild(button);
                        } else {
                            console.error("Google Pay button container not found.");
                        }
                    }
                }).catch(err => {
                    console.error("Google Pay initialization error: ", err);
                });
            },

            getGooglePaymentDataRequest: function () {
                const paymentDataRequest = {
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: [this.getBaseCardPaymentMethod()],
                    transactionInfo: this.getGoogleTransactionInfo(),
                    merchantInfo: {
                        merchantName: this.merchantName,
                        merchantId: this.merchantId
                    },
                    callbackIntents: ["PAYMENT_AUTHORIZATION"],
                    shippingAddressRequired: false,
                    shippingOptionRequired: false
                };
                return paymentDataRequest;
            },

            getGoogleTransactionInfo: function () {
                return {
                    totalPriceStatus: "FINAL",
                    totalPrice: this.grandTotal,
                    currencyCode: this.currentCurrency,
                    totalPriceLabel: "Grand Total"
                };
            },

            isValidRedirectUrl: function (url) {
                if (!url || typeof url !== 'string') {
                    return false;
                }

                // Allow relative URLs (no protocol)
                if (url.indexOf('://') === -1) {
                    return true;
                }

                try {
                    var parsedUrl = new URL(url);
                    var hostname = parsedUrl.hostname.toLowerCase();
                    
                    // Allow same origin
                    if (hostname === window.location.hostname) {
                        return true;
                    }
                    
                    // Allow trusted payment domains
                    var trustedDomains = [
                        'pay.google.com',
                        'pay.sandbox.google.com',
                        'pay.google.co.uk',
                        'pay.google.ie',
                        'payments.google.com',
                        'applepay.cdn-apple.com',
                        'apple-pay-gateway.apple.com',
                        'apple-pay-gateway-cert.apple.com',
                        'apple-pay-gateway-nc-pod1.apple.com',
                        'apple-pay-gateway-pr-pod1.apple.com'
                    ];
                    
                    return trustedDomains.indexOf(hostname) !== -1;
                } catch (e) {
                    return false;
                }
            },

            onPaymentAuthorized: function (paymentData, resolve) {
                var self = this;
                var placeOrderFlag = self.placeOrder();

                var waitforMinTime = new Date().getTime();
                var waitforMinTimeEnd = waitforMinTime;
                while (waitforMinTimeEnd < waitforMinTime + 5000) {
                    waitforMinTimeEnd = new Date().getTime();
                }

                var browser_width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
                var browser_height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

                if (placeOrderFlag) {
                    fullScreenLoader.startLoader();
                    $.ajax({
                        url: url.build('lloyds/googlepay/payment'),
                        type: 'POST',
                        showLoader: true,
                        data: {
                            form_key: $.mage.cookies.get('form_key'),
                            screen_height: browser_height,
                            screen_width: browser_width,
                            paymentData: JSON.stringify(paymentData)
                        }
                    }).done(function (response) {
                        if (response) {
                            if (response['url']) {
                                if (self.isValidRedirectUrl(response['url'])) {
                                    $('body').trigger('processStart');
                                    window.location = response['url'];
                                    return true;
                                } else {
                                    resolve({
                                        transactionState: 'ERROR',
                                        error: {
                                            reason: 'PAYMENT_DATA_INVALID',
                                            message: $t('Invalid redirect URL received from payment gateway')
                                        }
                                    });
                                    fullScreenLoader.stopLoader();
                                    return false;
                                }
                            } else if (response['form_data']) {
                                formBuilder(response['form_data']).submit();
                                return true;
                            } else if (response['3dsframe'] && response['data']) {
                                var ThreeDSURL = url.build('lloyds/paymentjs/threedsframe');
                                var redirectUrl = ThreeDSURL + '?ipg_id=' + encodeURIComponent(response['ipg_transaction_id']) + 
                                    '&order_id=' + encodeURIComponent(response['order_id']) + 
                                    '&threeds_data=' + encodeURIComponent(response['data']);
                                window.location.href = redirectUrl;
                            } else {
                                resolve({
                                    transactionState: 'ERROR',
                                    error: {
                                        reason: 'PAYMENT_DATA_INVALID',
                                        message: $t(response.message)
                                    }
                                });
                                fullScreenLoader.stopLoader();
                                return false;
                            }
                            return false;
                        } else {
                            resolve({
                                transactionState: 'ERROR',
                                error: {
                                    reason: 'PAYMENT_DATA_INVALID',
                                    message: $t(response.message)
                                }
                            });
                            fullScreenLoader.stopLoader();
                            return false;
                        }
                    }).fail(function (response) {
                        var errorMessage = $t('Payment processing failed. Please try again.');
                        if (response && response.responseJSON && response.responseJSON.message) {
                            errorMessage = response.responseJSON.message;
                        }
                        
                        messageList.addErrorMessage({
                            message: errorMessage
                        });
                        
                        fullScreenLoader.stopLoader();
                        window.location.href = response.url;
                    });
                } else {
                    messageList.addErrorMessage({
                        message: $t('Something went wrong with the payment. Please contact to customer support.')
                    });
                    window.location.href = response.url;
                    fullScreenLoader.stopLoader();

                }
            },

            getGooglePaymentsClient: function () {
                if (!this.paymentsClient) {
                    this.paymentsClient = new google.payments.api.PaymentsClient({
                        environment: this.paymentMode,
                        merchantInfo: {
                            merchantName: this.merchantName,
                            merchantId: this.merchantId
                        },
                        paymentDataCallbacks: {
                            onPaymentAuthorized: this.onPaymentAuthorized.bind(this)
                        }
                    });
                }
                return this.paymentsClient;
            },

            onGooglePaymentButtonClicked: function () {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    const paymentDataRequest = this.getGooglePaymentDataRequest();
                    const paymentsClient = this.getGooglePaymentsClient();
                    paymentsClient.loadPaymentData(paymentDataRequest);
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
