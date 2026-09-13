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
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'AutifyDigital_LloydscardnetPayment/js/lcnetredirect/form-builder',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Customer/js/model/customer',
    ],
    function (
        ko,
        $,
        Component,
        quote,
        url,
        customerData,
        errorProcessor,
        fullScreenLoader,
        formBuilder,
        globalMessageList,
        additionalValidators,
        redirectOnSuccessAction,
        customer
    ) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
            saveCards: window.checkoutConfig.payment.lcnetredirect.saveCards || [],
            selectedCard: ko.observable(''),
            paymentClientToken: null,
            saveCard: ko.observable(false),
            isCustomerLoggedIn: ko.observable(customer.isLoggedIn()), 
            defaults: {
                template: 'AutifyDigital_LloydscardnetPayment/payment/lcnetredirect'
            },

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
                var self = this;
                var selectedTokenId = this.selectedCard();
                var saveCard = this.saveCard();
                var redirectUrl = url.build('lcnetpayment/index/redirectPostData');
                if (selectedTokenId) {
                    redirectUrl = url.build('lcnetpayment/index/redirectPostData') + '?token_id=' + encodeURIComponent(selectedTokenId);
                }
                var browser_width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
                var browser_height = window.innerHeight || document.documentElement.clientHeight|| document.body.clientHeight;

                $.ajax({
                    showLoader: true,
                    url: redirectUrl,
                    data: {
                        browser_height: browser_height,
                        browser_width: browser_width,
                        save_card: saveCard ? 1 : 0
                    },
                    type: 'POST'
                }).done(function (response) {
                    console.log(response)
                    if (response && !response.error) {
                        self.redirectAfterPlaceOrder = true;
                        formBuilder(response).submit();
                        return true;
                    } else {
                        errorProcessor.process(response, this.messageContainer);
                        fullScreenLoader.stopLoader();
                        return false;
                    }
                }).fail(function (response) {
                    errorProcessor.process(response, this.messageContainer);
                    fullScreenLoader.stopLoader();
                });
                return false;
            },
            renderSaveCardsDropdown: function () {
                var options = [{
                    value: '',
                    label: 'Please select'
                }];

                this.saveCards.forEach(function (card) {
                    var cardBrand = card.brand;
                    options.push({
                        value: card.token_id,
                        label: card.masked + ' (' + cardBrand.charAt(0).toUpperCase() + cardBrand.slice(1) + ')'
                    });
                });

                return options;
            },
        });
    }
);
