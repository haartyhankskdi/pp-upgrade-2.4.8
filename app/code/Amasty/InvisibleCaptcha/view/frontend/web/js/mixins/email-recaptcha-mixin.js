define([
    'jquery'
], function ($) {
    'use strict';

    return function (EmailComponent) {
        return EmailComponent.extend({
            checkEmailAvailability: function () {
                this._super();

                $(document).trigger('am-recaptcha:rerender-forms');
            }
        });
    };
});
