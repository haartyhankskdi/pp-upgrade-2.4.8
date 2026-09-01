/**
 * Cookie bar logic
 */

define([
    'Amasty_GdprFrontendUi/js/modal-component',
    'jquery',
    'mage/translate',
    'Amasty_GdprFrontendUi/js/model/cookie-data-provider'
], function (
    ModalComponent,
    $,
    $t,
    cookieDataProvider,
) {
    'use strict';

    return ModalComponent.extend({
        defaults: {
            template: 'Amasty_GdprFrontendUi/components/elems',
            allowLink: '/',
            firstShowProcess: '0',
            cookiesName: [],
            domainName: '',
            setupModalTitle: $t('Please select and accept your Cookies Group'),
            isPopup: false,
            isDeclineEnabled: false,
            barLocation: null,
            selectors: {
                barSelector: '[data-amcookie-js="bar"]',
                acceptButton: '[data-amgdprcookie-js="accept"]',
                closeCookieBarButton: '[data-amcookie-js="close-cookiebar"]'
            }
        },

        initialize: function () {
            this._super();

            this.initEventHandlers();
            this.initModalWithData();

            return this;
        },

        initEventHandlers: function () {
            $(this.selectors.closeCookieBarButton).on('click', this.closeCookieBar.bind(this));
            this.closeOnEscapeButton();
        },

        closeOnEscapeButton: function () {
            const closeEvent = (event) => {
                if (event.keyCode === 27) {
                    this.closeCookieBar.call(this);
                    $(document).off('keydown', this.selectors.barSelector, closeEvent);
                }
            };

            $(document).on('keydown',  this.selectors.barSelector, closeEvent);
        },

        initButtonsEvents: function (buttons) {
            buttons.forEach(function (button) {
                if (button.dataJs !== 'settings') {
                    var elem = $('[data-amgdprcookie-js="' + button.dataJs + '"]');
                    elem.on('click', this.actionSave.bind(this, button, elem));
                    elem.attr('disabled', false);
                } else {
                    $('[data-amgdprcookie-js="' + button.dataJs + '"]')
                        .attr('disabled', false)
                        .on('click', this.openCookieSettingsModal.bind(this));
                }
            }.bind(this));

            $(this.selectors.acceptButton).focus();
        },

        openCookieSettingsModal: function () {
            this.getChild('gdpr-cookie-settings-modal').openModal();
        },

        /**
         * On allow all cookies callback
         */
        allowCookies: function () {
            this._super().done(function () {
                this.closeCookieBar();
            }.bind(this));
        },

        _performSave: function () {
            this._super();

            this.closeCookieBar();
        },

        closeCookieBar: function () {
            $(this.selectors.barSelector).remove();
        }
    });
});
