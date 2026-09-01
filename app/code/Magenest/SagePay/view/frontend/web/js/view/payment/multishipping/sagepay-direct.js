/**
 * Copyright © 2019 Magenest. All rights reserved.
 */
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'ko',
        'Magento_Ui/js/model/messageList',
        'Magento_Ui/js/model/messages',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Customer/js/model/customer',
        'mage/url',
        'mage/storage',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/translate',
    ],
    function (Component, $, ko, globalMessageList, messageContainer, fullScreenLoader, additionalValidators, redirectOnSuccessAction, quote, checkoutData, customer, url, storage, cardNumberValidator, creditCardData, setPaymentInformationExtended) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/multishipping/sagepay-direct',
                creditCardName: "",
                creditCardType: '',
                creditCardNumber: '',
                ccType: '',
                card: '',
                cardType: '',
                cardHolder: ' ',
                cardNumber: ' ',
                expireMonth: '',
                expireYear: '',
                expireDate: '',
                ccv: '',
            },

            initObservable: function () {
                var self = this;
                this._super();
                this.observe([
                    'creditCardNumber',
                    'creditCardType',
                    'creditCardName',
                    'sageCcType'
                ]);

                return this;
            },

            getSageCcType: function () {
                return JSON.parse(window.checkoutConfig.payment.magenest_sagepay_direct.cardType)
            },

            initialize: function () {
                this._super();
                var self = this;
                return this;
            },


            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    $.when(
                        setPaymentInformationExtended(
                            self.messageContainer,
                            self.getData(),
                            true
                        )
                    ).done(self.done.bind(self)).fail(self.fail.bind(self));
                }

                return false;
            },

            /**
             * {Function}
             */
            fail: function () {
                fullScreenLoader.stopLoader();

                return this;
            },

            /**
             * {Function}
             */
            done: function () {
                fullScreenLoader.stopLoader();
                $('#multishipping-billing-form').submit();

                return this;
            },

            isActive: function () {
                return true;
            },

            isShowLegend: function () {
                return true;
            },

            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            },

            validate: function () {
                return this.validateForm($('#' + this.getCode() + '-form'));
            },

            getCode: function () {
                return 'magenest_sagepay_direct';
            },

            getData: function () {
                var aaa = checkoutData;
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'isSageDirect': 1,
                        'billing_address': JSON.stringify(quote.billingAddress()),
                        'shipping_address': JSON.stringify(quote.shippingAddress()),
                        'cc_owner': this.creditCardName(),
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_ss_start_month': this.creditCardSsStartMonth(),
                        'cc_ss_start_year': this.creditCardSsStartYear(),
                        'cc_ss_issue': this.creditCardSsIssue(),
                        'cc_type': this.sageCcType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'browserInfo': JSON.stringify({
                            'BrowserJavaEnabled': navigator.javaEnabled(),
                            'BrowserColorDepth': screen.colorDepth,
                            'BrowserScreenHeight': screen.height,
                            'BrowserScreenWidth': screen.width,
                            'BrowserTZ': new Date().getTimezoneOffset()
                        })
                    }
                };
            }
        });
    }
);