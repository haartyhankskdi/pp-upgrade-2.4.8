/**
 * Ajax actions
 * @api
 */

define([
    'jquery',
    'underscore',
    'Amasty_InvisibleCaptcha/js/model/am-recaptcha',
], function ($, _, amReCaptchaModel) {
    'use strict';

    return {
        options : {
            reCaptchaSelector: 'am-recaptcha-block',
            reCaptchaErrorSelector: 'recaptcha-error',
            reCaptchaErrorMessageSelector: '.recaptcha-error-message'
        },

        /**
         * Ajax call
         * @param {Element} tokenField
         * @param {String} token
         * @returns {Deferred}
         */
        validateCaptcha: function (tokenField, token) {
            this.showErrorMessage(false);
            return $.ajax({
                url: amReCaptchaModel.checkoutRecaptchaValidateUrl,
                data: {
                    'g-recaptcha-response': token
                },
                type: 'POST'
            });
        },

        showErrorMessage: function (show) {
            var amReCaptchaBlock = $('.payment-method._active').find('#' + this.options.reCaptchaSelector);

            if (show) {
                amReCaptchaBlock.addClass(this.options.reCaptchaErrorSelector);
                if (!amReCaptchaBlock.find(this.options.reCaptchaErrorMessageSelector).length > 0) {
                    amReCaptchaBlock.append($('<div class="recaptcha-error-message">').html(this.getErrorMessage()));
                }
                amReCaptchaBlock.find(this.options.reCaptchaErrorMessageSelector).show();
            } else {
                amReCaptchaBlock.removeClass(this.options.reCaptchaErrorSelector);
                amReCaptchaBlock.find(this.options.reCaptchaErrorMessageSelector).hide();
            }
        },

        getErrorMessage: function () {
            return amReCaptchaModel.reCaptchaErrorMessage;
        }
    };
});
