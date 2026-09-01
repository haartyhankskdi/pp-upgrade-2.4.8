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
        'mage/storage',
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
    function (Component, $, ko, url, storage, globalMessageList, messageContainer, fullScreenLoader, additionalValidators, redirectOnSuccessAction, quote, customer, setPaymentInformationAction, setBillingAddressAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/payment/sagepay-form'
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
                    fullScreenLoader.startLoader();
                    this.isPlaceOrderActionAllowed(false);
                    $.when(
                        setPaymentInformationAction(
                            self.messageContainer,
                            {
                                method: this.getCode()
                            }
                        )
                    ).done(function () {
                        storage.post(
                            url.build('rest/V1/sagepay/buildForm'),
                            JSON.stringify({
                                data: {
                                    quote_id: quote.getQuoteId(),
                                    billing_address: JSON.stringify(quote.billingAddress()),
                                    shipping_address: JSON.stringify(quote.shippingAddress()),
                                    guest_email: quote.guestEmail
                                }
                            })
                        ).done(function (res) {
                            var response = JSON.parse(res);
                            if(response.success){
                                var formData = response.request,
                                    purchaseUrl = response.purchaseUrl;
                                var form = $('<form id="SagePayForm" name="SagePayForm" action="' + purchaseUrl + '" method="post">' +
                                    '</form>');
                                $('body').append(form);
                                for (var key in formData) {
                                    if (formData.hasOwnProperty(key)) {
                                        $('<input>').attr({
                                            type: 'hidden',
                                            name: key,
                                            value: formData[key]
                                        }).appendTo('#SagePayForm');
                                    }
                                }

                                form.submit();
                            }
                            if(response.error){
                                fullScreenLoader.stopLoader(true);
                                self.isPlaceOrderActionAllowed(true);
                                self.messageContainer.addErrorMessage({
                                    message: response.message
                                });
                            }
                        }).fail(function (res) {
                            self.messageContainer.addErrorMessage({
                                message: $.mage.__("Error, please try again")
                            });
                            fullScreenLoader.stopLoader(true);
                            self.isPlaceOrderActionAllowed(true);
                        });

                    });
                    return true;
                }

                return false;
            }
        });


    }
);