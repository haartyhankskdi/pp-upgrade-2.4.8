define([
    'Amasty_InvisibleCaptcha/js/model/am-recaptcha'
], function (amReCaptchaModel) {
    'use strict';

    return function (config) {
        amReCaptchaModel.setConfig(config);
    }
});
