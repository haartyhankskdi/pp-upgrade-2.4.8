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
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'mage/url',
    'domReady!'
], function ($, alert, $t, url) {
    'use strict';

    $.widget('mage.lbopcheckoutsolution', {
        options: {
            editFormSelector: '#edit_form',
            gateway: 'lbopcheckoutsolution'
        },
        isProcessing: false,

        _create: function () {
            var self = this;

            if (typeof window.order !== 'undefined' && typeof window.order.submit === 'function') {
                window._originalOrderSubmit = window.order.submit;
                window.order.submit = function () {
                    if ($('input[name="payment[method]"][value="' + self.options.gateway + '"]').is(':checked')) {
                        if (self.isProcessing) return false;
                        self.isProcessing = true;
                        self.placeOrder();
                        return false;
                    }
                    return window._originalOrderSubmit.apply(window.order);
                };
            }
        },

        placeOrder: function () {
            $('body').trigger('processStart');
            var self = this;
            var formData = $(this.options.editFormSelector).serialize();
                        
            $.ajax({
                url: $('#lcnet-admin-urls').data('placeorder-redirect-url'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response && response.order_id) {
                        self.redirectToPayment(response.order_id);
                    } else {
                        self.isProcessing = false;
                        $('body').trigger('processStop');
                        alert({ content: response.error_messages || $t('Failed to create order') });
                    }
                },
                error: function (xhr, status, error) {
                    self.isProcessing = false;
                    $('body').trigger('processStop');
                    alert({ content: $t('An error occurred while creating the order: ') + error });
                }
            });
        },

        redirectToPayment: function (orderId) {
            var self = this;
            
            $.ajax({
                url: $('#lcnet-admin-urls').data('checkout-redirect-url'),
                data: {
                    order_id: orderId['order_id'],
                    is_admin: 1
                },
                type: 'POST',
                dataType: 'json'
            }).done(function (response) {
                if (response && response.url) {
                    window.location = response.url;
                } else if (response && response.form_data) {
                    self.buildAndSubmitForm(response.form_data);
                } else {
                    $('body').trigger('processStop');
                    alert({ content: response.message || $t('Payment gateway error') });
                }
            }).fail(function () {
                $('body').trigger('processStop');
                alert({ content: $t('Failed to initialize payment') });
            });
        },

        buildAndSubmitForm: function (formData) {
            var form = document.createElement('form');
            form.method = formData.method || 'POST';
            form.action = formData.action || '';
            form.style.display = 'none';

            if (formData.fields && Array.isArray(formData.fields)) {
                formData.fields.forEach(function (field) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = field.name;
                    input.value = field.value;
                    form.appendChild(input);
                });
            }

            document.body.appendChild(form);
            form.submit();
        }
    });

    $(document).ready(function () {
        setTimeout(function () {
            $('#edit_form').lbopcheckoutsolution({
                editFormSelector: '#edit_form',
                gateway: 'lbopcheckoutsolution'
            });
        }, 500);
    });

    return $.mage.lbopcheckoutsolution;
});