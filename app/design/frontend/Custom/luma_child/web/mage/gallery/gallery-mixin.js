define([], function () {
    'use strict';

    return function (Gallery) {
        return Gallery.extend({
            /**
             * Builds the gallery API object, then wraps updateData() with a guard.
             *
             * Guarded against settings.fotoramaApi being unset when updateData()
             * runs - same fotorama-not-ready race as the _onGalleryLoaded guard in
             * Magento_ConfigurableProduct/js/configurable-mixin. Left unguarded,
             * "Cannot read properties of undefined (reading 'load')" is thrown from
             * inside updateData() and aborts _changeProductImage(), leaving the
             * gallery stuck.
             *
             * @private
             */
            initApi: function () {
                this._super();

                var settings = this.settings,
                    api = settings.$element.data('gallery'),
                    originalUpdateData;

                if (!api || typeof api.updateData !== 'function') {
                    return;
                }

                originalUpdateData = api.updateData;

                api.updateData = function (data) {
                    if (!settings.fotoramaApi || typeof settings.fotoramaApi.load !== 'function') {
                        return;
                    }

                    originalUpdateData.call(api, data);
                };
            }
        });
    };
});
