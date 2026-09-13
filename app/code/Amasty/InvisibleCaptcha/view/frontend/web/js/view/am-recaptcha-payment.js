define([
    'jquery',
    'underscore',
    'mageUtils',
    'Amasty_InvisibleCaptcha/js/view/am-recaptcha-abstract',
    'Amasty_InvisibleCaptcha/js/model/am-recaptcha',
    'Amasty_InvisibleCaptcha/js/action/am-recaptcha-validate',
    'Magento_Ui/js/model/messageList',
    "mage/translate"
], function (
    $,
    _,
    utils,
    Component,
    amReCaptchaModel,
    recaptchaValidate,
    messageList
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Amasty_InvisibleCaptcha/payment-recaptcha-container',
            isEnabledOnPayments: amReCaptchaModel.isEnabledOnPayments
        },

        renderReCaptcha: function (element) {
            if (window.grecaptcha && window.grecaptcha.render) {
                this.appendCaptcha();
                this.initCaptcha(element);
            } else {
                $(window).on('amcaptchaReady', function () {
                    this.appendCaptcha();
                    this.initCaptcha(element);
                }.bind(this));

                this.loadApi();
            }
        },

        getPaymentName: function (element) {
            var $element = $(element),
                // eslint-disable-next-line max-len
                paymentMethodField = $element.closest('.payment-method').find('.payment-method-title > input[type="radio"]');

            return paymentMethodField.length ? paymentMethodField.val() : '';
        },

        /**
         * Init captcha on each payment method
         * @param {Element} element
         * @return {void}
         */
        initCaptcha: function (element) {
            var $element = $(element),
                widgetId,
                listeners,
                id = utils.uniqueid(),
                $button = $element.closest('.payment-method-content').find('button[type="submit"]'),
                messagesContainer = $element.closest('.am-recaptcha-container').find('.messages-container'),
                paymentName = this.getPaymentName(element),
                $recaptchaBlock = $('<div>', {'id': recaptchaValidate.options.reCaptchaSelector})

            $(messagesContainer).attr('id', 'message-' + id);
            $element.attr('id', id);

            if (!$button.length) {
                $button = $('<button type="button" class="hidden" data-payment-name="' + paymentName + '"></button>');

                $button.insertAfter($element);
            }

            $button.before($recaptchaBlock);
            widgetId = window.grecaptcha.render($recaptchaBlock[0], this.getParameters($element, $button));
            $recaptchaBlock.append(
                $('<div class="recaptcha-error-message">').html(recaptchaValidate.getErrorMessage()).hide()
            );

            $button.click(function (event, data) {
                // Reset captcha in case of resubmitting the form
                if (this.needResetCaptcha(data)) {
                    this.resetCaptcha();
                    $element.val('');
                }

                if (!$element.val()) {
                    event.preventDefault(event);
                    event.stopImmediatePropagation();

                    if (amReCaptchaModel.getRecaptchaConfig().isInvisible) {
                        $(event.currentTarget).prop('disabled', true);
                        window.grecaptcha.execute(widgetId);
                    } else {
                        recaptchaValidate.showErrorMessage(true);
                    }
                } else {
                    this.setIsCaptchaValidationPassed(true);
                }
            }.bind(this));

            listeners = $._data($button[0], 'events').click;
            listeners.unshift(listeners.pop());

            amReCaptchaModel.tokenFields.push($element);
        },

        /**
         * Need Reset Invisible Captcha
         * @param {Object} data
         * @return {Boolean}
         */
        needResetCaptcha: function (data) {
            return data?.disableCaptchaReset !== true
                && amReCaptchaModel.getRecaptchaConfig().isInvisible
                && amReCaptchaModel.getRecaptchaConfig().recaptchaVersion === 3;
        },

        /**
         * Get captcha parameters
         * @param {Element} tokenField
         * @param {Element} element
         * @return {Object}
         */
        getParameters: function (tokenField, element) {
            return _.extend(amReCaptchaModel.getRecaptchaConfig(), {
                'callback': function (token) {
                    recaptchaValidate.validateCaptcha(tokenField, token)
                        .done(function (response) {
                            var $element = $(element),
                                isPlaceOrder = $element.prop('disabled');

                            $element.prop('disabled', false);

                            if (_.has(response, 'error') && response.error) {
                                this.resetCaptcha();
                                this.setIsCaptchaValidationPassed(false);
                                this.handleTokenError($(tokenField).attr('id'), response.message);

                                messageList.addErrorMessage({ message: response.message });
                            } else {
                                this.setIsCaptchaValidationPassed(true);
                                recaptchaValidate.showErrorMessage(false);
                                $(tokenField).val(token);

                                if (!$(element).hasClass('hidden') && isPlaceOrder) {
                                    $element.trigger('click', [{ disableCaptchaReset: true }]);
                                }
                            }
                        }.bind(this));
                }.bind(this),
                'expired-callback': this.resetCaptcha
            });
        },

        handleTokenError: function (tokenFieldId, message) {
            var container = $('#message-' + tokenFieldId),
                messageBlock = container.find('.message');

            messageBlock.html(message);
            container.show(0).delay(5000).hide('fast', function () {
                messageBlock.html('');
            });
        }
    });
});
