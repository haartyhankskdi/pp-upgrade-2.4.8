define([
    'jquery',
    'Kdi_Popup/js/cookie-helper',
    'domReady!'
], function ($, CookieHelper) {
    'use strict';

    $.widget('mage.kdiProductPopup', {
        options: {
            cookieName: 'kdi_product_popup_closed',
            cookieLifetime: 86400, // seconds (24 hours)
            subscribeUrl: '',
            productId: 0
        },

        /**
         * Step 2: only show the popup if the suppression cookie is absent.
         */
        _create: function () {
            var cookieValue = CookieHelper.get(this.options.cookieName);

            if (!cookieValue) {
                this._showPopup();
            }

            this._bind();
        },

        _showPopup: function () {
            this.element.show();
        },

        _hidePopup: function () {
            this.element.hide();
        },

        /**
         * Step 1: set a cookie that lives for 24 hours so the popup
         * does not show again until it expires. Used both when the
         * popup is dismissed and when the email is submitted, so
         * either action suppresses it for the same window.
         */
        _setCookie: function () {
            CookieHelper.set(this.options.cookieName, '1', this.options.cookieLifetime);
        },

        _bind: function () {
            var self = this;

            this.element.find('.kdi-popup-close').on('click', function () {
                self._setCookie();
                self._hidePopup();
            });

            this.element.find('#kdi-popup-form').on('submit', function (e) {
                e.preventDefault();
                self._submitEmail();
            });
        },

        _submitEmail: function () {
            var self = this,
                email = this.element.find('#kdi-popup-email').val(),
                responseBox = this.element.find('#kdi-popup-response');

            $.ajax({
                url: self.options.subscribeUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    email: email,
                    product_id: self.options.productId
                },
                showLoader: true
            }).done(function (response) {
                responseBox.text(response.message);

                if (response.success) {
                    self._setCookie();
                    setTimeout(function () {
                        self._hidePopup();
                    }, 1500);
                }
            }).fail(function () {
                responseBox.text($.mage.__('Something went wrong. Please try again.'));
            });
        }
    });

    return function (config, element) {
        $(element).kdiProductPopup(config);
    };
});