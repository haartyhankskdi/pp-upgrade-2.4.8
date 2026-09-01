/**
 * Copyright © 2019 Magenest. All rights reserved.
 */
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
    function (
        Component,
        $,
        ko,
        url,
        globalMessageList,
        messageContainer,
        fullScreenLoader,
        additionalValidators,
        redirectOnSuccessAction,
        quote,
        customer,
        setPaymentInformationAction,
        setBillingAddressAction
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/payment/sagepay-server'
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
                        $.post(
                            url.build('sagepay/server/build'),
                            {
                                form_key: $.cookie('form_key'),
                                quote_id: quote.getQuoteId(),
                                billing_address: JSON.stringify(quote.billingAddress()),
                                shipping_address: JSON.stringify(quote.shippingAddress()),
                                guest_email: quote.guestEmail
                            },
                            function (response) {
                                fullScreenLoader.stopLoader(true);
                                if(response.success){
                                    console.log(response);
                                    if (response.profile === "NORMAL") {
                                        window.location = response.nextUrl;
                                    } else if (response.profile == "LOW") {
                                        var iframe = $('<iframe id="server-low-profile" src="'+ response.nextUrl +'" style="width: 500px; height: 500px"></iframe>');
                                        $('.checkout-agreements-block-sage-server').after(iframe);
                                    }

                                }
                                if(response.error){
                                    self.isPlaceOrderActionAllowed(true);
                                    self.messageContainer.addErrorMessage({
                                        message: response.message
                                    });
                                }
                            },
                            "json"
                        );
                    });
                    return true;
                }

                return false;
            }
        });


    }
);
