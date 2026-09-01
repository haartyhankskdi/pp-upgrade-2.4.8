/**
 * Copyright © 2019 Magenest. All rights reserved.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'mage/url',
        'Magento_Ui/js/model/messageList',
        'Magento_Ui/js/model/messages',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/cookies'
    ],
    function (Component, $, ko, url, globalMessageList, messageContainer, fullScreenLoader, additionalValidators, redirectOnSuccessAction, quote, customer, setPaymentInformationAction, setBillingAddressAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/payment/sagepay-form',
                redirectAfterPlaceOrder: false
            },

            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'billing_address':JSON.stringify(quote.billingAddress()),
                        'shipping_address':JSON.stringify(quote.shippingAddress()),
                        'browserInfo': JSON.stringify({
                            'BrowserJavaEnabled': navigator.javaEnabled(),
                            'BrowserColorDepth': screen.colorDepth,
                            'BrowserScreenHeight': screen.height,
                            'BrowserScreenWidth': screen.width,
                            'BrowserTZ': new Date().getTimezoneOffset()
                        })
                    }
                };
            },

            getPaypalIcon: function() {
                return window.checkoutConfig.payment.magenest_sagepay_paypal.paypal_icon;
            },

            afterPlaceOrder: function () {
                window.location.replace(window.checkoutConfig.payment.magenest_sagepay_paypal.redirect_url);
            }
        });


    }
);
