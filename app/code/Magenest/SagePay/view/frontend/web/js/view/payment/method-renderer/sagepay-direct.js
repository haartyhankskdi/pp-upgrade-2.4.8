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
        'Magento_Customer/js/model/customer',
        'mage/url',
        'mage/storage',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'mage/translate',
    ],
    function (Component, $, ko, globalMessageList, messageContainer, fullScreenLoader, additionalValidators, redirectOnSuccessAction, quote, customer, url, storage, cardNumberValidator, creditCardData) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/payment/sagepay-direct',
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
            isFormVisible: ko.observable(true),


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

            getSageCcType: function() {
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

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();

                        }
                    );

                    return true;
                }

                return false;
            },


            realPlaceOrder: function () {
                var self = this;
                console.log('real place order');
                this.getPlaceOrderDeferredObject()
                    .fail(
                        function () {
                            console.log('fail');
                            self.isPlaceOrderActionAllowed(true);
                            fullScreenLoader.stopLoader(true);
                        }
                    )
                    .done(
                        function () {
                            console.log('done');
                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );
            },


            afterPlaceOrder: function () {
                console.log('after place order');
                var self = this;
                storage.post(
                    url.build('rest/V1/sagepay/get3DInfo'),
                    JSON.stringify({
                        form_key: window.checkoutConfig.formKey
                    })
                ).done(function (response) {
                    var response = JSON.parse(response);
                    if (response.success) {
                        if (response.is3dSecure) {
                            var form = $('<form id="sage_3d_form" action="' + response.ACSURL + '" method="post">' +
                                '</form>');
                            $('body').append(form);

                            var dataFields = {
                                'MD': "MD",
                                'PAReq': "PaReq",
                                'CReq': "CReq",
                                'threeDSSessionData': 'threeDSSessionData'
                            };
                            for (var key in dataFields) {
                                if (response.hasOwnProperty(key)) {
                                    var sageName = dataFields[key];
                                    if (sageName == 'CReq') sageName = sageName.toLowerCase();
                                    $('<input>').attr({
                                        type: 'hidden',
                                        name: sageName,
                                        value: response[key]
                                    }).appendTo('#sage_3d_form');
                                }
                            }
                            $('<input>').attr({
                                type: 'hidden',
                                name: 'TermUrl',
                                value: url.build('sagepay/direct/postBack')
                            }).appendTo('#sage_3d_form');
                            form.submit();

                        } else {
                            redirectOnSuccessAction.execute();
                        }


                    }
                    if (response.error) {
                        self.messageContainer.addErrorMessage({
                            message: response.message
                        });
                        fullScreenLoader.stopLoader(true);
                        self.isPlaceOrderActionAllowed(true);
                    }
                }).fail(function (response) {
                    self.messageContainer.addErrorMessage({
                        message: $.mage.__("Error, please try again")
                    });
                    fullScreenLoader.stopLoader(true);
                    self.isPlaceOrderActionAllowed(true);
                });

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
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'isSageDirect': 1,
                        'billing_address':JSON.stringify(quote.billingAddress()),
                        'shipping_address':JSON.stringify(quote.shippingAddress()),
                        'cc_owner':this.creditCardName(),
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