/**
 * reCaptcha model
 */

define([
    'ko',
    'underscore'
], function (ko, _) {
    'use strict';

    return {
        onLoadCallback: 'amInvisibleCaptchaOnloadCallback',
        isEnabledOnPayments: false,
        isScriptLoaded: false,
        tokenFields: [],
        url: 'https://www.google.com/recaptcha/api.js',
        isCaptchaAppended: false,
        checkoutRecaptchaValidateUrl: null,
        invisibleCaptchaCustomForm: false,
        isValidationPassed: ko.observable(false),
        recaptchaConfig: {
            lang: 'hl=en',
            size: 'invisible'
        },
        formToProtect: '',
        reCaptchaErrorMessage: 'Prove you are not a robot',
        recaptchaVersion: null,
        isInvisible: null,

        setConfig: function (config) {
            if (_.has(config, 'recaptchaConfig')) {
                this.setRecaptchaConfig(config.recaptchaConfig);
            }

            if (_.has(config, 'formsToProtect')) {
                this.setFormsList(config.formsToProtect);
            }

            this.checkoutRecaptchaValidateUrl = config.checkoutRecaptchaValidateUrl;
            this.invisibleCaptchaCustomForm = config.invisibleCaptchaCustomForm;
            this.isEnabledOnPayments = !!config.isEnabledOnPayments;
            this.reCaptchaErrorMessage = config.reCaptchaErrorMessage;
            this.recaptchaVersion = config.recaptchaVersion;
        },

        setRecaptchaConfig: function (config) {
            _.extend(this.recaptchaConfig, config);
        },

        getRecaptchaConfig: function () {
            return this.recaptchaConfig;
        },

        setFormsList: function (formsList) {
            this.formToProtect = formsList;
        },

        getFormsList: function () {
            return this.formToProtect;
        }
    };
});
