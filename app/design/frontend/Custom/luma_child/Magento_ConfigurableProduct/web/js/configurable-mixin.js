define(['jquery', 'underscore', 'mage/template'], function ($, _, mageTemplate) {
    'use strict';

    return function (Configurable) {
        return $.widget('mage.configurable', Configurable, {
            /**
             * Callback which fired after gallery gets initialized.
             *
             * Guarded against galleryObject.returnCurrentImages() throwing when the
             * fotorama gallery hasn't finished attaching yet (e.g. "Cannot read
             * properties of undefined (reading 'data')"). Left unguarded, that
             * exception aborts _create() before the swatch/dropdown options get
             * populated, so the crash surfaces as empty Brand/Size dropdowns.
             *
             * @param {HTMLElement} element - DOM element associated with gallery.
             */
            _onGalleryLoaded: function (element) {
                var galleryObject = element.data('gallery');

                if (!galleryObject || typeof galleryObject.returnCurrentImages !== 'function') {
                    return;
                }

                try {
                    this.options.mediaGalleryInitial = galleryObject.returnCurrentImages();
                } catch (e) {
                    this.options.mediaGalleryInitial = null;
                }
            },

            /**
             * Initialize tax configuration, initial settings, and options values.
             *
             * Guarded against priceBox('option') throwing "cannot call methods on
             * priceBox prior to initialization" when the price box widget on this
             * product page hasn't attached yet by the time _create() runs. Left
             * unguarded, that exception aborts _create() before the swatch/dropdown
             * options get populated, same failure category as the gallery guard above.
             *
             * @private
             */
            _initializeOptions: function () {
                var options = this.options,
                    gallery = $(options.mediaGallerySelector),
                    priceBoxOptions;

                try {
                    priceBoxOptions = this._getPriceBoxElement().priceBox('option').priceConfig || null;
                } catch (e) {
                    priceBoxOptions = null;
                }

                if (priceBoxOptions && priceBoxOptions.optionTemplate) {
                    options.optionTemplate = priceBoxOptions.optionTemplate;
                }

                if (priceBoxOptions && priceBoxOptions.priceFormat) {
                    options.priceFormat = priceBoxOptions.priceFormat;
                }
                options.optionTemplate = mageTemplate(options.optionTemplate);
                options.tierPriceTemplate = $(this.options.tierPriceTemplateSelector).html();

                options.settings = options.spConfig.containerId ?
                    $(options.spConfig.containerId).find(options.superSelector) :
                    this.element.parents(this.options.selectorProduct).find(options.superSelector);

                options.values = options.spConfig.defaultValues || {};
                options.parentImage = $('[data-role=base-image-container] img').attr('src');

                this.inputSimpleProduct = this.element.find(options.selectSimpleProduct);

                gallery.data('gallery') ?
                    this._onGalleryLoaded(gallery) :
                    gallery.on('gallery:loaded', this._onGalleryLoaded.bind(this, gallery));
            },

            /**
             * Returns prices for configured products.
             *
             * Same priceBox('option') race as _initializeOptions above: this runs
             * synchronously during _create() (via _configureForValues), so without
             * this guard the crash just moves here instead of being fixed.
             *
             * @param {*} config - Products configuration
             * @returns {*}
             * @private
             */
            _calculatePrice: function (config) {
                var displayPrices,
                    newPrices = this.options.spConfig.optionPrices[_.first(config.allowedProducts)] || {};

                try {
                    displayPrices = this._getPriceBoxElement().priceBox('option').prices;
                } catch (e) {
                    displayPrices = {};
                }

                _.each(displayPrices, function (price, code) {
                    displayPrices[code].amount = newPrices[code] ? newPrices[code].amount - displayPrices[code].amount : 0;
                });

                return displayPrices;
            }
        });
    };
});
