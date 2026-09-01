/**
 * Webkul_OutOfStockNotification
 * app/code/Webkul/OutOfStockNotification/view/frontend/web/js/section.js
 */
define([
    'uiComponent',
    'jquery',
    'mage/translate',
    'jquery/validate'
], function (Component, $, $t) {
    'use strict';

    return Component.extend({

        initialize: function () {
            this._super();
            this.bindEvents();
            return this;
        },

        bindEvents: function () {
            var self = this;

            $(document).on('click', '#wk-oosn-button', function (e) {
                e.preventDefault();
                self.submitNotify();
            });
        },

        /**
         * Show/hide loader
         */
        toggleLoader: function (show) {
            $('.wk-loading-mask-outer').toggleClass('wk-display-none', !show);
        },

        /**
         * Render message inside #wk-oosn-warning
         */
        showMessage: function (text, type) {
            var $warning = $('#wk-oosn-warning');

            $warning
                .removeClass('success error')
                .addClass(type)
                .text(text)
                .show();
        },

        /**
         * Basic field validation
         */
        validateFields: function (name, email) {
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!name) {
                this.showMessage($t('Please enter your first name.'), 'error');
                return false;
            }

            if (!email || !emailPattern.test(email)) {
                this.showMessage($t('Please enter a valid email address.'), 'error');
                return false;
            }

            return true;
        },

        /**
         * Submit notify request via AJAX
         */
        submitNotify: function () {
            var self = this,
                name = $('#oosn_name').val().trim(),
                email = $('#oosn_email').val().trim(),
                productId = $('#oosn_product_id').val();

            if (!this.validateFields(name, email)) {
                return;
            }

            this.toggleLoader(true);

            $.ajax({
                url: '/outofstocknotification/product/addcustomer/',
                type: 'POST',
                dataType: 'json',
                data: {
                    name: name,
                    email: email,
                    product_id: productId,
                    form_key: $('input[name="form_key"]').val() || ''
                }
            }).done(function (response) {
                self.toggleLoader(false);

                if (response && response.msg) {
                    self.showMessage(response.msg, 'success');
                    $('#oosn_name').val('');
                    $('#oosn_email').val('');
                } else {
                    var errorMsg = (response && typeof response.error === 'string')
                        ? response.error
                        : (response && response.message) || $t('Something went wrong. Please try again.');

                    self.showMessage(errorMsg, 'error');
                }
            }).fail(function (response) {
                self.toggleLoader(false);

                var message = $t('Something went wrong. Please try again.');

                if (response && response.responseJSON && response.responseJSON.message) {
                    message = response.responseJSON.message;
                }

                self.showMessage(message, 'error');
            });
        }
    });
});