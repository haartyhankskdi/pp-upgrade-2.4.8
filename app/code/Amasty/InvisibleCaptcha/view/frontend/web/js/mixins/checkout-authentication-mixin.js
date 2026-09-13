define([
    'jquery'
], function ($) {
    'use strict';

    return function (Component) {
        return Component.extend({
            initialize: function () {
                this._super();

                if (document.querySelector('form[data-role="login"]')) {
                    $(document).trigger('am-recaptcha:rerender-forms');

                    return this;
                }

                var observer = new MutationObserver(function () {
                    if (document.querySelector('form[data-role="login"]')) {
                        observer.disconnect();
                        $(document).trigger('am-recaptcha:rerender-forms');
                    }
                });

                observer.observe(document.body, {childList: true, subtree: true});

                setTimeout(function () {
                    observer.disconnect();
                }, 15000);

                return this;
            }
        });
    };
});
