/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
require([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'mage/url',
    'AutifyDigital_LloydscardnetPayment/js/model/paymentjs-error',
    'jquery/ui',
    'domReady!'
], function ($, mageTemplate, alert, $t, url, getPaymentJsErrorMessage) {
    'use strict';

    $.widget('mage.lcnetpaymentjs', {
        options: {
            editFormSelector: '#edit_form',
            paymentMethod: 'lcnetpaymentjs',
            containerSelector: '.admin-payment-method-lcnetpaymentjs',
            fieldsContainerSelector: '.payment-fields-container'
        },
        paymentClientToken: null,
        isProcessing: false,
        formInitialized: false,

        _create: function() {
            this._captureOrderSubmit();
            this.setupEventListeners();
            this.autoSelectPaymentMethod();
            this.checkPaymentMethodState();
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

        _captureOrderSubmit: function() {
            var self = this;
            
            if (typeof window.order !== 'undefined' && window.order && 
                typeof window.order.submit === 'function' &&
                typeof window._originalOrderSubmit === 'undefined') {
                
                window._originalOrderSubmit = window.order.submit;
                window.order.submit = function(e) {
                    if ($('input[name="payment[method]"]:checked').val() === self.options.paymentMethod) {
                        if (self.isProcessing) return false;
                        self.isProcessing = true;
                        self.placeOrder(e || new Event('submit'));
                        return false;
                    }
                    return window._originalOrderSubmit.call(window.order);
                };
            }
            if (typeof window.Form !== 'undefined' && window.Form && 
                Form.prototype && Form.prototype.submit && !Form.prototype._originalSubmit) {
                Form.prototype._originalSubmit = Form.prototype.submit;
                Form.prototype.submit = function() {
                    if ($(self.options.editFormSelector)[0] === this.element && 
                        $('input[name="payment[method]"]:checked').val() === self.options.paymentMethod) {
                        if (self.isProcessing) return false;
                        self.isProcessing = true;
                        self.placeOrder(new Event('submit'));
                        return false;
                    }
                    return this._originalSubmit();
                };
            }
            
            try {
                var formElement = $(this.options.editFormSelector);
                if (formElement.length > 0) {
                    var form = formElement[0];
                    if (form && typeof form.submit === 'function' && !form._lcnetOriginalSubmit) {
                        form._lcnetOriginalSubmit = form.submit;
                        form.submit = function() {
                            if ($('input[name="payment[method]"]:checked').val() === self.options.paymentMethod) {
                                if (self.isProcessing) return false;
                                self.isProcessing = true;
                                self.placeOrder(new Event('submit'));
                                return false;
                            }
                            form._lcnetOriginalSubmit.apply(form);
                        };
                    }
                }
            } catch (e) {
                console.error('Error setting up form submit interceptor:', e);
            }
        },

        setupEventListeners: function() {
            var self = this;
            
            $(document).on('change', 'input[name="payment[method]"]', function() {
                if ($(this).val() !== self.options.paymentMethod) {
                    self.formInitialized = false;
                }
                self.checkPaymentMethodState();
            });
            
            $(document).on('change', 'input[name="order[shipping_method]"]', function() {
                if ($('input[name="payment[method]"]:checked').val() === self.options.paymentMethod) {
                    self.formInitialized = false;
                    setTimeout(function() {
                        self.checkPaymentMethodState();
                    }, 300);
                }
            });
           
            document.addEventListener('click', function(e) {
                var target = e.target;
                if (target.matches('#submit_order_top_button') || target.closest('.order-totals-actions .submit-button')) {
                    if ($('input[name="payment[method]"][value="' + self.options.paymentMethod + '"]').is(':checked')) {
                        if (self.isProcessing) {
                            e.preventDefault();
                            return false;
                        }
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        self.isProcessing = true;
                        self.placeOrder(e);
                        return false;
                    }
                }
            }, true);
            
            $(this.options.editFormSelector).on('submit beforesubmit.' + this.options.paymentMethod, function(e) {
                if ($('input[name="payment[method]"][value="' + self.options.paymentMethod + '"]').is(':checked')) {
                    if (self.isProcessing) {
                        e.preventDefault();
                        return false;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    self.isProcessing = true;
                    self.placeOrder(e);
                    return false;
                }
            });
        },
        
        checkPaymentMethodState: function() {
            var selectedMethod = $('input[name="payment[method]"]:checked').val();
            if (selectedMethod === this.options.paymentMethod) {
                this.showPaymentForm();
            } else {
                $(this.options.containerSelector).hide();
                this.destroyPaymentForm();
            }
        },
        
        showPaymentForm: function() {
            var self = this;
            $(this.options.containerSelector).show();
            this.resetPaymentFields();
            
            if (typeof window.firstdata !== 'undefined' && 
                typeof window.firstdata.createPaymentForm === 'function') {
                setTimeout(function() {
                    self.initializePaymentForm();
                }, 300);
            } else {
                setTimeout(function() {
                    self.checkFirstDataAvailability(10);
                }, 500);
            }
            
            $(this.options.fieldsContainerSelector).show();
        },
        
        resetPaymentFields: function() {
            var ccFields = document.getElementsByClassName('payment-fields');
            for (var i = 0; i < ccFields.length; i++) {
                if (ccFields[i].classList) {
                    while (ccFields[i].firstChild) {
                        ccFields[i].removeChild(ccFields[i].firstChild);
                    }
                }
            }
            this.formInitialized = false;
            this.destroyPaymentForm();
        },
        
        destroyPaymentForm: function() {
            if (window.paymentIPGForm) {
                try {
                    if (typeof window.paymentIPGForm.destroy === 'function') {
                        window.paymentIPGForm.destroy();
                    } else {
                        console.warn('paymentIPGForm.destroy is not a function:', window.paymentIPGForm);
                    }
                } catch (e) {
                    console.error('Error destroying payment form:', e);
                } finally {
                    window.paymentIPGForm = null;
                }
            }
        },
        
        checkFirstDataAvailability: function(attempts) {
            var self = this;
            
            if (typeof window.firstdata !== 'undefined' &&
                typeof window.firstdata.createPaymentForm === 'function') {
                
                if ($('input[name="payment[method]"]:checked').val() === self.options.paymentMethod) {
                    self.initializePaymentForm();
                }
                return;
            }
            
            if (attempts <= 0) {
                alert({
                    content: $t('Payment system failed to initialize. Please refresh the page or try again later.')
                });
                return;
            }
            
            setTimeout(function() {
                self.checkFirstDataAvailability(attempts - 1);
            }, 500);
        },
        
        initializePaymentForm: function() {
            var self = this;
            
            if (window.paymentFormInitializing === true) return;
            window.paymentFormInitializing = true;
            this.destroyPaymentForm();
            
            if (typeof window.firstdata === "undefined" || 
                typeof window.firstdata.createPaymentForm !== 'function') {
                return;
            }
            
            var cardElement = document.querySelector('[data-cc-card]');
            var cvvElement = document.querySelector('[data-cc-cvv]');
            var expElement = document.querySelector('[data-cc-exp]');
            var nameElement = document.querySelector('[data-cc-name]');
            
            if (!cardElement || !cvvElement || !expElement || !nameElement) {
                alert({
                    content: $t('Payment form elements not found. Please refresh the page.')
                });
                return;
            }
            
            var configElement = $('#lcnetpayment-config');
            try {
                var configData = configElement.attr('data-lcnet-config');
                window.adminPaymentConfig = typeof configData === 'string' ? 
                    JSON.parse(configData) : 
                    (configData && typeof configData === 'object' ? configData : {});
            } catch (e) {
                window.paymentFormInitializing = false;
                window.adminPaymentConfig = {
                    lcnetpaymentjs: {
                        placeholder_card: 'Card Number',
                        placeholder_cvv: 'CVV',
                        placeholder_exp: 'MM / YY',
                        placeholder_name: 'Card Holder Name'
                    }
                };
            }
            
            const paymentConfig = window.adminPaymentConfig.lcnetpaymentjs || {};
            const config = {
                fields: {
                    card: {
                        selector: '[data-cc-card]',
                        placeholder: paymentConfig.placeholder_card || 'Card Number'
                    },
                    cvv: {
                        selector: '[data-cc-cvv]',
                        placeholder: paymentConfig.placeholder_cvv || 'CVV'
                    },
                    exp: {
                        selector: '[data-cc-exp]',
                        placeholder: paymentConfig.placeholder_exp || 'MM / YY'
                    },
                    name: {
                        selector: '[data-cc-name]',
                        placeholder: paymentConfig.placeholder_name || 'Card Holder Name'
                    }
                }
            };

            const allowedBrands = paymentConfig.allowed_brands || [];
            if (allowedBrands.length > 0) {
                config.fields.card.allowedBrands = allowedBrands;
            }

            const hooks = {
                preFlowHook: function(callback) {
                    if (!callback || typeof callback !== 'function') return;
                    
                    $('body').trigger('processStart');
                    
                    var customerId = '';
                    if (typeof window.order !== 'undefined' && window.order.customerId) {
                        customerId = window.order.customerId;
                    }
                    var requestUrl = $('#lcnet-admin-urls').data('auth-url');
                    $.ajax({
                        url: requestUrl,
                        type: 'POST',
                        data: {
                            form_key: $('input[name="form_key"]').val(),
                            customer_id: customerId
                        },
                        dataType: 'json',
                        success: function(response) {
                            $('body').trigger('processStop');
                            
                            if (response.error) {
                                alert({
                                    content: response.message || $t('Error communicating with payment server')
                                });
                                return;
                            }
                            
                            callback(response);
                        },
                        error: function() {
                            $('body').trigger('processStop');
                            alert({
                                content: $t('An error occurred while communicating with the payment server.')
                            });
                        }
                    });
                }
            };
            
            window.onCreate = function(paymentForm) {
                if (!paymentForm) return;
                
                window.paymentIPGForm = paymentForm;
                self.formInitialized = true;
                
                const ccFields = window.document.getElementsByClassName('payment-fields');
                for (let i = 0; i < ccFields.length; i++) {
                    if (ccFields[i].classList) {
                        ccFields[i].classList.remove('disabled');
                        ccFields[i].classList.remove('empty');
                    }
                }
            };
            
            try {
                window.firstdata.createPaymentForm(config, hooks, window.onCreate);
            } catch (e) {
                alert({
                    content: $t('Could not initialize payment form: ') + e.message
                });
            }
            
            setTimeout(function() {
                window.paymentFormInitializing = false;
            }, 1000);
        },
        
        placeOrder: function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            
            $('body').trigger('processStart');
            
            if (!this.validatePaymentForm()) {
                this.isProcessing = false;
                $('body').trigger('processStop');
                return false;
            }
            
            if (!$(this.options.editFormSelector).valid()) {
                this.isProcessing = false;
                $('body').trigger('processStop');
                return false;
            }
            
            if (!window.paymentIPGForm) {
                this.isProcessing = false;
                $('body').trigger('processStop');
                alert({
                    content: $t('Payment form not initialized. Please refresh and try again.')
                });
                return false;
            }
            
            var self = this;
            
            try {
                window.paymentIPGForm.onSubmit(
                    function(clientToken) {
                        self.paymentClientToken = clientToken;
                        self.prepareOrderWithToken(clientToken);
                    },
                    function(error) {
                        var paymentConfig = (window.adminPaymentConfig || {}).lcnetpaymentjs || {};

                        self.isProcessing = false;
                        $('body').trigger('processStop');
                        alert({
                            content: getPaymentJsErrorMessage(error, paymentConfig.allowed_brands || [])
                        });
                    }
                );
            } catch (e) {
                this.isProcessing = false;
                $('body').trigger('processStop');
                alert({
                    content: $t('An error occurred during payment processing: ') + e.message
                });
                return false;
            }
            
            return false;
        },
        
        validatePaymentForm: function() {            
            var emptyFields = [];
            var isValid = true;
            var fieldMap = {
                '#cc-card': 'Card Number',
                '#cc-cvv': 'CVV',
                '#cc-exp': 'Expiration Date',
                '#cc-name': 'Card Holder Name'
            };
            
            Object.keys(fieldMap).forEach(function(selector) {
                if ($(selector).hasClass('empty')) {
                    emptyFields.push(fieldMap[selector]);
                    isValid = false;
                }
            });
            
            if (!isValid) {
                alert({
                    content: $t('Please complete the following fields: ') + emptyFields.join(', ')
                });
            }
            
            return isValid;
        },

        prepareOrderWithToken: function(token) {
            this.addFormField('payment_token_input', 'payment[token]', token);
            this.addFormField('payment_token_id_input', 'payment[token_id]', token);
            this.addFormField('payment_method_flag', 'payment[method_used]', this.options.paymentMethod);
            
            this.submitOrderWithPaymentData();
        },
        
        addFormField: function(id, name, value) {
            var input = document.getElementById(id) || document.createElement('input');
            input.type = 'hidden';
            input.id = id;
            input.name = name;
            input.value = value;
            $(this.options.editFormSelector).append(input);
            
            return input;
        },
        
        submitOrderWithPaymentData: function() {
            var self = this;
            var formData = $(this.options.editFormSelector).serialize();
            var originalFormAction = $(this.options.editFormSelector).attr('action');
            
            $.ajax({
                url: $('#lcnet-admin-urls').data('placeorder-url'),
                type: 'post',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response && response.order_id) {
                        self.finalizePaymentForOrder(response.order_id);
                    } else if (response && response.error) {
                        self.isProcessing = false;
                        $('body').trigger('processStop');
                        if (originalFormAction) {
                            $(self.options.editFormSelector).attr('action', originalFormAction);
                        }
                        alert({
                            content: response.error_messages || $t('Failed to create order')
                        });
                    } else {
                        self.isProcessing = false;
                        $('body').trigger('processStop');
                        if (originalFormAction) {
                            $(self.options.editFormSelector).attr('action', originalFormAction);
                        }
                        alert({
                            content: $t('Unexpected response from server')
                        });
                    }
                },
                error: function(xhr, status, error) {
                    self.isProcessing = false;
                    $('body').trigger('processStop');
                    if (originalFormAction) {
                        $(self.options.editFormSelector).attr('action', originalFormAction);
                    }
                    alert({
                        content: $t('An error occurred while creating the order: ') + error
                    });
                }
            });
        },
        
        finalizePaymentForOrder: function(orderId) {
            var self = this;
            var browser_data = {
                width: window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth,
                height: window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight
            };
            
            $.ajax({
                showLoader: true,
                url: $('#lcnet-admin-urls').data('process-url'),
                data: {
                    form_key: $('input[name="form_key"]').val(),
                    browser_height: browser_data.height,
                    browser_width: browser_data.width,
                    order_id: orderId.order_id,
                    client_token: this.paymentClientToken,
                },
                dataType: 'json',
                type: 'POST',
                success: function(response) {
                    self.isProcessing = false;
                    
                    if (response) {
                        if (response['url']) {
                            if (self.isValidRedirectUrl(response['url'])) {
                                window.location = response['url'];
                            } else {
                                $('body').trigger('processStop');
                                alert({
                                    content: $t('Invalid redirect URL received from payment gateway')
                                });
                            }
                        } else if (response['form_data']) {
                            require(['AutifyDigital_LloydscardnetPayment/js/lcnetredirect/form-builder'], function(formBuilder) {
                                formBuilder(response['form_data']).submit();
                            });
                        } else if (response['success']) {
                            window.location = $('#lcnet-admin-urls').data('orderview-url');
                        } else {
                            $('body').trigger('processStop');
                            alert({
                                content: response.message || $t('An error occurred during payment processing.')
                            });
                        }
                    } else {
                        $('body').trigger('processStop');
                        alert({
                            content: $t('Invalid response from payment gateway')
                        });
                    }
                },
                error: function() {
                    self.isProcessing = false;
                    $('body').trigger('processStop');
                    alert({
                        content: $t('An error occurred during payment processing')
                    });
                }
            });
        },
        
        autoSelectPaymentMethod: function() {
            var paymentMethodRadio = $('input[name="payment[method]"][value="' + this.options.paymentMethod + '"]');
            
            if (paymentMethodRadio.length && !paymentMethodRadio.is(':checked')) {
                var anyMethodSelected = $('input[name="payment[method]"]:checked').length > 0;
                
                if (!anyMethodSelected) {
                    paymentMethodRadio.prop('checked', true);
                    paymentMethodRadio.trigger('change');
                    
                    if (typeof window.order !== 'undefined' && typeof window.order.setPaymentMethod === 'function') {
                        window.order.setPaymentMethod(this.options.paymentMethod);
                    }
                }
            }
        }
    });

    $(document).ready(function() {
        var paymentMethodRadio = $('input[name="payment[method]"][value="lcnetpaymentjs"]');
        if (paymentMethodRadio.length) {
            var parentContainer = paymentMethodRadio.closest('.admin__payment-method-content');
            if (parentContainer.length) {
                $('#lcnetpayment-container').detach().appendTo(parentContainer);
            } else {
                var methodContainer = paymentMethodRadio.closest('.admin__payment-method');
                if (methodContainer.length) {
                    $('#lcnetpayment-container').detach().appendTo(methodContainer);
                }
            }
        }
        
        $('#edit_form').lcnetpaymentjs({
            editFormSelector: '#edit_form',
            paymentMethod: 'lcnetpaymentjs'
        });
    });

    return $.mage.lcnetpaymentjs;
});