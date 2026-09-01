define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/mage'
], function ($, _, mageTemplate) {
    'use strict';

    $.widget('magezon.newsletter', {
        _create: function () {
            this.initValidation();
        },

        initValidation: function () {
            var self = this;

            this.element.mage('validation', {
                submitHandler: function () {
                    if ($.isEmptyObject(self.element.validate().invalid)) {
                        self.ajaxSubmit($(self.element));
                    }
                }
            });
        },

    ajaxSubmit: function(form) {
    form.addClass('loading');
    form.find('button').attr('disabled', 'disabled');

    var self = this;
    var data = $(form).serialize();

    $.ajax({
        url: form.attr('action'),
        data: data,
        type: 'post',
        dataType: 'json',
        success: function(res) {
            form.find('button').removeAttr('disabled');
            form.removeClass('loading');
            form.parent().find('.mgz-newsletter-message').remove();

            var $popupContent = form.closest('.popupbuilder-popup-modal').find('.submit-success');

            if (res.status) {
              
                $popupContent.children().hide();

               var message = '<div class="mgz-newsletter-message mgz-newsletter-message-success" style="text-align:center; padding:115px 20px; font-size:22px; line-height:1.6;">' +
              res.message +
              '</div>';
                $popupContent.append(message);

                form[0].reset();
            } else {
                var error = '<div class="mgz-newsletter-message mgz-newsletter-message-error">' + res.message + '</div>';
                form.parent().append(error);
            }
            
            if (res.status && self.options.emailAjaxUrl) {
                $.ajax({
                    url: self.options.emailAjaxUrl,
                    data: data,
                    type: 'post',
                    dataType: 'json'
                });
            }
        }
    });
}


    });

    return $.magezon.newsletter;
});
