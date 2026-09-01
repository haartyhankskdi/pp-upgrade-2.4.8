define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, confirm, $t) {
    'use strict';

    $.widget('mage.amcformPrompt', {
        options: {
            modalClass: 'amcform-popup-block -active',
            responsive: true,
            title: $t('This form can be submitted only once. Ready to proceed?'),
            cancellationLink: '',
            actions: {},
            isShowed: false
        },

        _create: function () {
            var self = this,
                options = this.options,
                form = $(this.element).closest('.amform-form');

            self.options.actions = {
                cancel: function () {
                    self.options.isShowed = false;
                }
            };

            options.buttons = [{
                text: $t('No'),
                class: 'amcform-button -error',
                click: function () {
                    this.closeModal();
                }
            }, {
                text: $t('Yes'),
                class: 'amcform-button -default',
                click: function () {
                    this.closeModal();
                    form.submit();
                }
            }];

            this.element.click(function (e) {
                e.preventDefault();

                if (!self.options.isShowed) {
                    confirm(options);

                    self.options.isShowed = true;
                } else {
                    form.submit();
                }
            });
        }
    });

    return $.mage.amcformPrompt
});
