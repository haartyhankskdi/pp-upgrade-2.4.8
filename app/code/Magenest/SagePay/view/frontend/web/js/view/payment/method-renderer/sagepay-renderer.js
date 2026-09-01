/**
 * Copyright © 2019 Magenest. All rights reserved.
 */
/*browser:true*/
/*global define*/
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
        'Magento_Ui/js/modal/modal',
        'mage/loader',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/cookies',
        'mage/translate',
    ],
    function (Component, $, ko, globalMessageList, messageContainer, fullScreenLoader, additionalValidators, redirectOnSuccessAction, quote, customer, url, modal) {
        'use strict';

        var checkout;
        var modalDropin;
        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/payment/sagepay',
                cardIdentifier: "",
                expiryDate: "",
                redirectAfterPlaceOrder: false,
                creditCardName: "",
                merchantSessionKey: "",
                merchantKeyExpire: ""
            },
            messageContainer: messageContainer,

            hasCard: window.checkoutConfig.payment.magenest_sagepay.hasCard,
            isSave: window.checkoutConfig.payment.magenest_sagepay.isSave,
            savedCards: ko.observableArray(JSON.parse(window.checkoutConfig.payment.magenest_sagepay.saveCards)),
            selectedCard: ko.observable(0),

            displaySaveCard: (window.checkoutConfig.payment.magenest_sagepay.isSave && customer.isLoggedIn()),
            displayGiftAid: window.checkoutConfig.payment.magenest_sagepay.isGiftAid,
            isFormVisible: ko.observable(true),

            saveCardCheckbox: ko.observable(false),
            giftAidCheckbox: ko.observable(false),

            isCheckoutDropinLoaded: false,
            useDropIn: window.checkoutConfig.payment.magenest_sagepay.useDropin,
            dropInMode: window.checkoutConfig.payment.magenest_sagepay.dropinMode,

            initObservable: function () {
                var self = this;
                this._super();
                this.observe([
                    'creditCardName',
                    'displaySaveCard',
                    'expiryDate',
                    'merchantSessionKey'
                ]);
                this.expiryDate = ko.computed(function () {
                    var date = "",
                        month = self.creditCardExpMonth(),
                        year = self.creditCardExpYear();
                    if (month && (month < 10)) {
                        date = '0'.concat(month);
                    } else if (month && (month >= 10)) {
                        date = month
                    }
                    if (year) {
                        year = year.slice(2);
                        date = date.concat(year);
                    }
                    return date;
                });

                setInterval(function () {
                    self.isMerchantKeyExpire();
                }, 1000);
                return this;
            },

            /**
             * Initialize view.
             *
             * @return {exports}
             */
            initialize: function () {
                this._super();
                var self = this;
                this.selectedCard.subscribe(function (value) {
                    if (!value) {
                        self.isFormVisible(true);
                        self.displaySaveCard(customer.isLoggedIn() && self.isSave);
                    } else {
                        self.isFormVisible(false);
                        self.displaySaveCard(false);
                    }
                });

            },

            loadSageJs: function (callback) {
                var isTest = window.checkoutConfig.payment.magenest_sagepay.isSandbox;
                var jsUrl = "https://pi-live.sagepay.com/api/v1/js/sagepay.js";
                if (isTest) {
                    jsUrl = "https://pi-test.sagepay.com/api/v1/js/sagepay.js";
                }
                if (typeof sagepayCheckout === "undefined") {
                    $.ajax({
                        url: jsUrl,
                        dataType: 'script',
                        success: function (result) {
                            callback();
                        }
                    });
                }
                else {
                    callback();
                }
            },

            getCode: function () {
                return 'magenest_sagepay';
            },

            isActive: function () {
                return true;
            },

            validateForm: function (form) {
                return $(form).validation() && $(form).validation('isValid');
            },

            validate: function () {
                if (!this.useDropIn) {
                    return this.validateForm($('#' + this.getCode() + '-form'));
                } else {
                    return true;
                }
            },

            isShowLegend: function () {
                return true;
            },

            getMerchantSessionKey: function (callback) {
                var self = this;
                if(this.isMerchantKeyExpire()) {
                    $.ajax({
                            type: "GET",
                            data: {
                                form_key: $.cookie('form_key')
                            },
                            url: url.build('sagepay/checkout/merchantSessionKey'),
                            success: function (response) {
                                if(response.success){
                                    self.merchantSessionKey(response.data.merchantSessionKey);
                                    self.merchantKeyExpire = Date.parse(response.data.expiry);
                                }
                                if(response.error){
                                    console.log(response.data);
                                    self.messageContainer.addErrorMessage({
                                        message: JSON.stringify(response.data)
                                    });
                                }
                            },
                            dataType: "json"
                        }
                    ).done(function () {
                        callback();
                    });
                }else{
                    callback();
                }
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this;
                if (self.validate() && additionalValidators.validate()) {
                    fullScreenLoader.startLoader();
                    this.loadSageJs(function () {
                        self.getMerchantSessionKey(function () {
                            fullScreenLoader.stopLoader();
                            if (self.selectedCard()) {
                                sagepayOwnForm({
                                    merchantSessionKey: self.merchantSessionKey()
                                }).activateReusableCardIdentifier({
                                    reusableCardIdentifier: self.selectedCard(),
                                    securityCode: self.creditCardVerificationNumber(),
                                    onActivated: function (status) {
                                        if (status.success) {
                                            self.realPlaceOrder();
                                        } else {
                                            console.log(status);
                                            self.messageContainer.addErrorMessage({
                                                message: self.printErrorResponse(status)
                                            });
                                            self.isPlaceOrderActionAllowed(true);
                                        }
                                    }
                                });
                            } else {
                                if (self.useDropIn) {
                                    if(self.dropInMode == 'modal') {
                                        self.initSageDropin();
                                        $('#sagepay-dropin-modal').modal("openModal");
                                    }else{
                                        if(!self.isCheckoutDropinLoaded){
                                            self.initSageDropin();
                                        }else {
                                            self.getMerchantSessionKey(function () {
                                                checkout.tokenise({
                                                    newMerchantSessionKey: self.merchantSessionKey()
                                                });
                                            })
                                        }
                                    }
                                } else {
                                    self.isPlaceOrderActionAllowed(false);
                                    var cardDetail = {
                                        cardholderName: self.creditCardName(),
                                        cardNumber: self.creditCardNumber(),
                                        expiryDate: self.expiryDate(),
                                        securityCode: self.creditCardVerificationNumber()
                                    };
                                    sagepayOwnForm({merchantSessionKey: self.merchantSessionKey()}).tokeniseCardDetails({
                                        cardDetails: cardDetail,
                                        onTokenised: function (result, response) {
                                            if (result.success) {
                                                self.cardIdentifier = result['cardIdentifier'];
                                                self.realPlaceOrder();
                                            } else {
                                                console.log(result);
                                                self.messageContainer.addErrorMessage({
                                                    message: self.printErrorResponse(result)
                                                });
                                                self.isPlaceOrderActionAllowed(true);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    });
                }
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'card_identifier': this.cardIdentifier,
                        'merchant_sessionKey': this.merchantSessionKey(),
                        'save': this.saveCardCheckbox(),
                        'selected_card': this.selectedCard(),
                        'gift_aid': this.giftAidCheckbox()
                    }
                }
            },

            realPlaceOrder: function () {
                var self = this;
                this.getPlaceOrderDeferredObject()
                    .fail(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                            fullScreenLoader.stopLoader(true);
                            if (typeof modalDropin !== 'undefined') {
                                modalDropin.closeModal();
                            }
                            self.merchantKeyExpire = 0;
                        }
                    ).done(
                    function () {
                        self.afterPlaceOrder();

                        if (self.redirectAfterPlaceOrder) {
                            redirectOnSuccessAction.execute();
                        }
                    }
                );
            },

            afterPlaceOrder: function () {
                var self = this;
                $.ajax({
                    url: url.build('sagepay/checkout/threedSecure'),
                    data: {
                        form_key: $.cookie('form_key')
                    },
                    dataType: "json",
                    type: 'POST',
                    success: function (response) {
                        if (response.success) {
                            //default pay -> success page
                            if (response.defaultPay) {
                                redirectOnSuccessAction.execute();
                            }
                            if (response.threeDSercueActive) {
                                var formData = response.formData;
                                var form = $('<form id="sage_3d_form" action="' + response.threeDSercueUrl + '" method="post">' +
                                    '</form>');
                                $('body').append(form);
                                for (var key in formData) {
                                    if (formData.hasOwnProperty(key)) {
                                        $('<input>').attr({
                                            type: 'hidden',
                                            name: key,
                                            value: formData[key]
                                        }).appendTo('#sage_3d_form');
                                    }
                                }
                                form.submit();
                            }

                        }
                        if (response.error) {
                            self.messageContainer.addErrorMessage({
                                message: response.message
                            });
                            fullScreenLoader.stopLoader(true);
                            self.isPlaceOrderActionAllowed(true);
                        }
                    },
                    error: function (response) {
                        self.messageContainer.addErrorMessage({
                            message: $.mage.__("Error, please try again")
                        });
                        fullScreenLoader.stopLoader(true);
                        self.isPlaceOrderActionAllowed(true);
                    }
                })
            },

            getInstructions: function () {
                return window.checkoutConfig.payment.magenest_sagepay.instruction;
            },

            initSageDropin: function () {
                var self = this;
                if (typeof checkout === 'undefined') {
                    checkout = sagepayCheckout({
                        merchantSessionKey: self.merchantSessionKey(),
                        containerSelector: '#sagepay-dropin-container',
                        onTokenise: function (tokenisationResult) {
                            fullScreenLoader.startLoader();
                            if (tokenisationResult.success) {
                                fullScreenLoader.stopLoader(true);
                                self.cardIdentifier = tokenisationResult.cardIdentifier;
                                self.realPlaceOrder();
                                return;
                            }
                            self.messageContainer.addErrorMessage({
                                message: $.mage.__("Payment error")
                            });
                            self.isPlaceOrderActionAllowed(true);
                            fullScreenLoader.stopLoader(true);
                        }
                    });
                    checkout.form();
                }
                self.isCheckoutDropinLoaded = true;

                $('input[name="payment[method]"]').click(function () {
                    if ($('input[name="payment[method]"]:checked').val() == self.getCode()) {
                        $('#sagepay-dropin-container').css('height', '164px');
                        $('#payment-iframe').css('height', '164px');
                    }
                });
            },

            setupSageDropIn: function () {
                var self = this;
                if(this.dropInMode == 'modal') {
                    modalDropin = modal({
                        type: 'popup',
                        title: this.getTitle(),
                        responsive: true,
                        innerScroll: true,
                        buttons: [
                            {
                                text: $.mage.__('Cancel'),
                                class: 'action checkout',
                                click: function () {
                                    this.closeModal();
                                }
                            },
                            {
                                text: $.mage.__('Place order'),
                                class: 'action primary checkout',
                                click: function () {
                                    self.getMerchantSessionKey(function () {
                                        checkout.tokenise({
                                            newMerchantSessionKey: self.merchantSessionKey()
                                        });
                                    })
                                }
                            }
                        ]
                    }, $('#sagepay-dropin-modal'));
                }else{
                    this.loadSageJs(function () {
                        self.getMerchantSessionKey(function () {
                            self.initSageDropin();
                        })
                    })
                }
            },

            isMerchantKeyExpire: function () {
                var date = new Date();
                if(date.getTime() > (this.merchantKeyExpire - 5000)){
                    return true;
                }
                return false;
            },

            getPlaceOrderButtonLabel: function () {
                if(this.useDropIn && (this.dropInMode == 'modal')){
                    return 'Continue';
                }else{
                    return 'Place Order';
                }
            },

            printErrorResponse: function (response) {
                function displayArrayObjects(arrayObjects) {
                    var len = arrayObjects.length, text = "";
                    for (var i = 0; i < len; i++) {
                        var myObject = arrayObjects[i];
                        for (var x in myObject) {
                            text += ( x + ": " + myObject[x] + " ");
                        }
                        text += " . ";
                    }
                    return text;
                }
                if (typeof response.errors !== 'undefined') {
                    if(Array.isArray(response.errors)){
                        return displayArrayObjects(response.errors);
                    }
                }
                return "Payment error";
            }
        });

    }
);