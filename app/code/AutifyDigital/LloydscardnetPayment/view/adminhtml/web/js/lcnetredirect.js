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
    'AutifyDigital_LloydscardnetPayment/js/lcnetredirect/form-builder',
    'domReady!'
], function ($, mageTemplate, alert, $t, url, formBuilder) {
    'use strict';

    var instance = null;
    
    var realOriginalOrderSubmit = null;
    
    $.widget('mage.lcnetredirect', {
        options: {
            editFormSelector: '#edit_form',
            orderSaveUrl: null,
            controller: null,
            gateway: null,
            nativeAction: null
        },
        redirectAfterPlaceOrder: false,
        originalFormAction: null,
        isProcessing: false,
        initialized: false,
        formInitialized: false,

        _create: function () {
            var self = this;
            instance = this;
            
            // Capture form's native submit method early
            if ($(this.options.editFormSelector)[0] && !$(this.options.editFormSelector)[0]._lcnetOriginalSubmit) {
                var form = $(this.options.editFormSelector)[0];
                form._lcnetOriginalSubmit = form.submit;
                form.submit = function () {
                    if ($('input[name="payment[method]"][value="' + self.options.gateway + '"]').is(':checked')) {
                        if (self.isProcessing) {
                            return false;
                        }
                        self.isProcessing = true;
                        setTimeout(function () {
                            self._placeOrderHandler(new Event('submit'));
                        }, 10);
                        return false;
                    }
                    return form._lcnetOriginalSubmit.apply(form);
                };
            }
            
            // Override the order.submit method ONLY ONCE
            this._initializeOrderSubmitOverride();

            // Use capture phase to intercept click events as early as possible
            document.addEventListener('click', function(e) {
                if ($(e.target).is('#submit_order_top_button, .order-totals-actions .submit-button') ||
                    $(e.target).closest('#submit_order_top_button, .order-totals-actions .submit-button').length) {
                    if ($('input[name="payment[method]"][value="' + self.options.gateway + '"]').is(':checked')) {
                        if (self.isProcessing) {
                            e.preventDefault();
                            return false;
                        }
                        
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        
                        self.isProcessing = true;
                        setTimeout(function () {
                            self._placeOrderHandler(e);
                        }, 10);
                        return false;
                    }
                }
            }, true); // true enables capture phase

            // Add a regular jQuery event handler as backup
            $(document).on('click', '#submit_order_top_button, .order-totals-actions .submit-button', function (e) {
                if ($('input[name="payment[method]"][value="' + self.options.gateway + '"]').is(':checked')) {
                    if (self.isProcessing) {
                        e.preventDefault();
                        return false;
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    self.isProcessing = true;
                    setTimeout(function () {
                        self._placeOrderHandler(e);
                    }, 10);
                    return false;
                }
            });

            // Add form submit handler
            $(this.options.editFormSelector).on('submit', function (e) {
                if ($('input[name="payment[method]"][value="' + self.options.gateway + '"]').is(':checked')) {
                    if (self.isProcessing) {
                        e.preventDefault();
                        return false;
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    self.isProcessing = true;
                    setTimeout(function () {
                        self._placeOrderHandler(e);
                    }, 10);
                    return false;
                }
            });

            // Add onsubmit handler directly to the form
            var form = $(this.options.editFormSelector)[0];
            if (form && !form._lcnetOnSubmitAttached) {
                form._lcnetOnSubmitAttached = true;
                var originalOnSubmit = form.onsubmit;
                form.onsubmit = function(e) {
                    if ($('input[name="payment[method]"][value="' + self.options.gateway + '"]').is(':checked')) {
                        e.preventDefault();
                        if (!self.isProcessing) {
                            self.isProcessing = true;
                            setTimeout(function() {
                                self._placeOrderHandler(e);
                            }, 10);
                        }
                        return false;
                    }
                    return originalOnSubmit ? originalOnSubmit.apply(this, arguments) : true;
                };
            }

            this.initialize();
        },

        submitOrderWithPaymentData: function () {
            $('body').trigger('processStart');
            
            var formData = $(this.options.editFormSelector).serialize();

            $.ajax({
                url: $('#lcnet-admin-urls').data('placeorder-redirect-url'),
                type: 'post',
                context: this,
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response && response.order_id) {
                        this.finalizePaymentForOrder(response.order_id);
                    } else if (response && response.error) {
                        this.isProcessing = false;
                        $('body').trigger('processStop');
                        alert({
                            content: response.error_messages || $t('Failed to create order')
                        });
                    } else {
                        this.isProcessing = false;
                        $('body').trigger('processStop');
                        alert({
                            content: $t('Unexpected response from server')
                        });
                    }
                },
                error: function (xhr, status, error) {
                    this.isProcessing = false;
                    $('body').trigger('processStop');
                    alert({
                        content: $t('An error occurred while creating the order: ') + error
                    });
                }
            });
        },

        _placeOrderHandler: function (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            
            // Change the form action to our custom controller
            var originalAction = $(this.options.editFormSelector).attr('action');
            this.originalFormAction = originalAction;
            
            var customOrderUrl = $('#lcnet-admin-urls').data('placeorder-redirect-url');
            $(this.options.editFormSelector).attr('action', customOrderUrl);
            
            var self = this;
            self.submitOrderWithPaymentData();
            
            // Prevent any default submission
            return false;
        },

        finalizePaymentForOrder: function(orderId) {
            if (orderId) {
                $('body').trigger('processStart');
                var self = this;
                var formKey = $('input[name="form_key"]').val();
                $.ajax({
                    showLoader: true,
                    url: $('#lcnet-admin-urls').data('redirectpostdata-url'),
                    data: {
                        form_key: formKey,
                        order_id: orderId['order_id']
                    },
                    type: 'POST'
                }).done(function (response) {
                    if(response && !response.error) {
                        self.redirectAfterPlaceOrder = true;
                        formBuilder(response).submit();
                        location.reload();
                        return true;
                    } else {
                        errorProcessor.process(response, this.messageContainer);
                        $('body').trigger('processStart');
                        return false;
                    }
                }).fail(function (response) {
                    errorProcessor.process(response, this.messageContainer);
                    $('body').trigger('processStart');
                });
            } else {
                this.isProcessing = false;
                $('body').trigger('processStop');
                alert({
                    content: $t('Order creation failed. No order ID was returned.')
                });
            }
        },

        initialize: function () {
            var self = this;

            if (this.initialized) {
                return this;
            }

            this.initialized = true;

            if (typeof window === 'undefined') {
                return this;
            }

            return this;
        },
        
        // Initialize order.submit override with module-level storage
        _initializeOrderSubmitOverride: function() {
            var self = this;
            
            // Check if we've already set up the override at module level
            if (window._lcnetOrderSubmitOverrideComplete) {
                return;
            }
            
            var attemptCount = 0;
            var maxAttempts = 20;
            
            var setupOverride = function() {
                if (typeof window.order !== 'undefined' && typeof window.order.submit === 'function') {
                    
                    // CRITICAL: Only capture the original if we haven't already
                    if (!realOriginalOrderSubmit) {
                        realOriginalOrderSubmit = window.order.submit;
                        console.log('Captured original order.submit function');
                    }
                    
                    // Mark as complete to prevent re-initialization
                    window._lcnetOrderSubmitOverrideComplete = true;
                    
                    // Set up our override using the module-level reference
                    window.order.submit = function () {
                        var gateway = self.options ? self.options.gateway : 'lcnetredirect';
                        var paymentMethodSelector = 'input[name="payment[method]"][value="' + gateway + '"]';
                        
                        try {
                            var $paymentMethod = $(paymentMethodSelector);
                            if ($paymentMethod.length && $paymentMethod.is(':checked')) {
                                if (self.isProcessing) {
                                    return false;
                                }
                                self.isProcessing = true;
                                setTimeout(function () {
                                    self._placeOrderHandler(new Event('submit'));
                                }, 10);
                                return false;
                            }
                        } catch (err) {
                            console.error('Error checking payment method:', err);
                        }
                        
                        // Call the REAL original function stored at module level
                        if (realOriginalOrderSubmit && typeof realOriginalOrderSubmit === 'function') {
                            return realOriginalOrderSubmit.apply(window.order, arguments);
                        }
                        return true;
                    };
                    
                } else if (attemptCount < maxAttempts) {
                    attemptCount++;
                    setTimeout(setupOverride, 100);
                }
            };
            
            setupOverride();
        }
    });

    $(document).ready(function () {
        setTimeout(function() {
            $('#edit_form').lcnetredirect({
                editFormSelector: '#edit_form',
                orderSaveUrl: $('#lcnet-admin-urls').data('placeorder-redirect-url'),
                gateway: 'lcnetredirect'
            });
        }, 500);
    });

    return $.mage.lcnetredirect;
});