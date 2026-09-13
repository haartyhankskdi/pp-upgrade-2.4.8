/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
define([], function () {
    'use strict';

    var SDK_URL = 'https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js';
    var loadPromise = null;

    function isSdkReady() {
        return !!(window.customElements && window.customElements.get('apple-pay-button'));
    }

    function loadSdk() {
        return new Promise(function (resolve) {
            if (isSdkReady()) {
                resolve(true);
                return;
            }

            if (!document.querySelector('script[data-lloyds-applepay-sdk]')) {
                var script = document.createElement('script');
                script.src = SDK_URL;
                script.async = true;
                script.setAttribute('data-lloyds-applepay-sdk', '1');
                document.head.appendChild(script);
            }

            var attempts = 0;
            var maxAttempts = 50;
            var checkSdk = setInterval(function () {
                if (isSdkReady()) {
                    clearInterval(checkSdk);
                    resolve(true);
                } else if (++attempts >= maxAttempts) {
                    clearInterval(checkSdk);
                    console.warn('Lloyds Apple Pay: SDK did not load; Apple Pay hidden.');
                    resolve(false);
                }
            }, 100);
        });
    }

    return function ensureApplePaySdk() {
        if (!loadPromise) {
            loadPromise = loadSdk();
        }
        return loadPromise;
    };
});
