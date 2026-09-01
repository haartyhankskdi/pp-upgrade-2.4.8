/**
 * Cookie modal logic
 */

define([
    'Amasty_GdprFrontendUi/js/modal-component',
    'jquery',
    'underscore',
    'mage/translate',
    'Amasty_GdprFrontendUi/js/model/cookie-data-provider'
], function (
    ModalComponent,
    $,
    _,
    $t,
    cookieDataProvider
) {
    'use strict';

    return ModalComponent.extend({
        defaults: {
            template: 'Amasty_GdprFrontendUi/components/elems',
            timeout: null,
            groups: [],
            cookieModal: null,
            element: {
                modal: '[data-amgdpr-js="modal"]',
                form: '[data-amcookie-js="form-cookie"]',
                container: '[data-role="gdpr-cookie-container"]',
                field: '[data-amcookie-js="field"]',
                groups: '[data-amcookie-js="groups"]',
                policy: '[data-amcookie-js="policy"]',
                overlay: '[data-amgdpr-js="overlay"]',
                acceptButton: '[data-amgdprcookie-js="accept"]'
            },
            setupModalTitle: $t('Please select and accept your Cookies Group'),
        },

        initialize: function () {
            this._super();

            this.initModalWithData();
            this.addResizeEvent();
            this.addCloseEvents();
            this.setModalHeight();

            return this;
        },

        initModalWithData: function () {
            this._super().done(function (cookieData) {
                this.groups = cookieData.groupData;
                this.initInformationModal();
            }.bind(this));
        },

        initInformationModal: function () {
            var links = $('[data-amgdprcookie-js="information"]');

            links.on('click', function (event) {
                event.preventDefault();
                var groupData = this.groups.find(function (group) {
                    return group.groupId === event.currentTarget.dataset.groupid;
                });

                this.getInformationModal(groupData);
            }.bind(this));
        },

        initButtonsEvents: function (buttons) {
            buttons.forEach(function (button) {
                var elem = $('[data-amgdprcookie-js="' + button.dataJs + '"]');
                elem.on('click', this.actionSave.bind(this, button, elem));
                elem.attr('disabled', false);
            }.bind(this));

            $(this.element.acceptButton).focus();
        },

        /**
         * Create/open settings modal
         * @param {Event} event
         */
        getSettingsModal: function (event) {
            event.preventDefault();
            cookieDataProvider.getCookieData().done(function (data) {
                if (this.setupModal) {
                    this.setupModal.items(data.groupData);
                    this.setupModal.openModal();

                    return;
                }

                this.initSetupModal(data.groupData);
            }.bind(this));
        },

        closeModal: function () {
            $(this.element.modal).removeClass('_show');
            $(this.element.overlay).remove();
            $(window).off('resize', this.resizeFunc);
        },

        /**
         * On allow all cookies callback
         */
        allowCookies: function () {
            this._super().done(function () {
                this.closeModal();
            }.bind(this));
        },

        addResizeEvent: function () {
            this.resizeFunc = _.throttle(this.setModalHeight, 150).bind(this);
            $(window).on('resize', this.resizeFunc);
        },

        addCloseEvents: function () {
            $(this.element.modal).on('amclosemodal', this.closeModal.bind(this));

            const closeEvent = (event) => {
                if (event.keyCode === 27) {
                    this.closeModal.call(this);
                    $(document).off('keydown', this.element.modal, closeEvent);
                }
            };
            $(document).on('keydown',  this.element.modal, closeEvent);
        },

        setModalHeight: function () {
            var policyHeight = $(this.element.policy).innerHeight(),
                windowHeight = window.innerHeight,
                groupsContainer = $(this.element.groups);

            groupsContainer.height(windowHeight - policyHeight + 'px');
        }
    });
});
