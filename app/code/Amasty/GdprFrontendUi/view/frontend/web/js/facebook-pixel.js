/**
 * initialization of facebook Pixel
 */

define([
    'jquery',
    'underscore',
    'mage/cookies'
], function ($, _) {
    'use strict';

    /**
     * @param {Object} config
     */
    return function (config) {
        var allowedCookies,
            disallowedCookies,
            isAllowedToRunScript,
            facebookPixelCookieName = '_fbp';

        disallowedCookies = $.mage.cookies.get('amcookie_disallowed') || '';
        allowedCookies = $.mage.cookies.get('amcookie_allowed') || '';
        isAllowedToRunScript = !!allowedCookies.length
            && (!disallowedCookies || disallowedCookies.indexOf(facebookPixelCookieName) === -1)

        if (isAllowedToRunScript) {
            fbq(config.callMethod, config.arguments);
        }
    };
});
