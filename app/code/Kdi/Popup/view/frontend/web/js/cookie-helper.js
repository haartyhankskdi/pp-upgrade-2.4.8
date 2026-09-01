/**
 * Kdi_Popup cookie helper
 *
 * Plain JS cookie utility — does NOT depend on mage/cookies, so it
 * sidesteps the lifetimeToExpires() bug where options.expires gets
 * passed in as something other than a Date object.
 *
 * Usage:
 *   define(['Kdi_ProductPopup/js/cookie-helper'], function (CookieHelper) {
 *       CookieHelper.set('kdi_popup_shown', '1', 86400); // 24hr lifetime in seconds
 *       var shown = CookieHelper.get('kdi_popup_shown');
 *       CookieHelper.remove('kdi_popup_shown');
 *   });
 */
define([], function () {
    'use strict';

    return {
        /**
         * @param {String} name
         * @param {String} value
         * @param {Number} [lifetimeInSeconds] - omit for a session cookie
         */
        set: function (name, value, lifetimeInSeconds) {
            var expires = '';

            if (lifetimeInSeconds) {
                var date = new Date();
                date.setTime(date.getTime() + (lifetimeInSeconds * 1000));
                expires = '; expires=' + date.toUTCString();
            }

            document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value) + expires + '; path=/';
        },

        /**
         * @param {String} name
         * @return {String|null}
         */
        get: function (name) {
            var nameEQ = encodeURIComponent(name) + '=';
            var cookies = document.cookie.split(';');

            for (var i = 0; i < cookies.length; i++) {
                var cookie = cookies[i].trim();

                if (cookie.indexOf(nameEQ) === 0) {
                    return decodeURIComponent(cookie.substring(nameEQ.length));
                }
            }

            return null;
        },

        /**
         * @param {String} name
         */
        remove: function (name) {
            document.cookie = encodeURIComponent(name) + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
        }
    };
});