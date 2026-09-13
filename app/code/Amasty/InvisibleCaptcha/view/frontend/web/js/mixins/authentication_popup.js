define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (authenticationPopup) {
        authenticationPopup.showModal = wrapper.wrapSuper(authenticationPopup.showModal, function () {
            this._super();

            $(document).trigger('am-recaptcha:rerender-forms');
        });

        return authenticationPopup;
    };
});
