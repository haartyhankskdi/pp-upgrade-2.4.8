define([
    'jquery',
    'matchMedia',
    'collapsible',
    'domReady!'
], function ($, mediaCheck) {
    'use strict';

    var component = {
        options: {
            selectors: {
                menu: '[data-amreports-js="menu"]',
                menuList: '[data-amreports-js="menu-list"]',
                menuContainer: '[data-amreports-js="menu-container"]',
                header: '[data-amreports-js="heading"]',
                content: '[data-amreports-js="content"]',
                accordion: '[data-amreports-js="accordion"]',
                otherPage: '.amreports-other-pages',
                menuWrapper: '.menu-wrapper'
            },
            classes: {
                active: '-active',
                close: 'amreports-close',
                fixed: '_fixed'
            }
        },

        /**
         * Initialize component
         *
         * @public
         * @returns {void}
         */
        init: function () {
            this._onViewportChange();
            this._pageToFixed();
        },

        /**
         * @private
         * @param {Array} accordions
         * @param {Boolean} state
         * @returns {void}
         */
        _accordionsListener: function (accordions, state) {
            var self = this;

            accordions.forEach(function (element) {
                self._toggleCollapse(element, state);
            });

            $(self.options.selectors.content).off('click keydown');
        },

        /**
         * On/off collapsible functionality
         *
         * @private
         * @param {Object} element
         * @param {Boolean} state
         * @returns {void}
         */
        _toggleCollapse: function (element, state) {
            var accordion = $(element);

            accordion.collapsible(!state ? 'activate' : 'deactivate');
            accordion.collapsible('option', 'collapsible', state);
        },

        /**
         * @private
         * @returns {void}
         */
        _pageToFixed: function () {
            var otherPage = $(this.options.selectors.otherPage),
                menuWrapper = otherPage.find(this.options.selectors.menuWrapper);

            if (!menuWrapper.hasClass(this.options.classes.fixed)) {
                menuWrapper.addClass(this.options.classes.fixed);
            }
        },

        /**
         * Listen to viewport changes and make menu items as a dropdown on mobile view
         *
         * @private
         * @returns {void}
         */
        _onViewportChange: function () {
            var self = this,
                accordions = this._getAccordions();

            mediaCheck({
                media: '(min-width: 1024px)',
                entry: function () {
                    self._accordionsListener(accordions, false);
                    self._toggleMenuListener(false);
                },
                exit: function () {
                    self._accordionsListener(accordions, true);
                    self._toggleMenuListener(true);
                }
            });
        },

        /**
         * Show/hide reports menu items listener
         *
         * @private
         * @param {Boolean} state
         * @returns {void}
         */
        _toggleMenuListener: function (state) {
            var self = this,
                menu = $(this.options.selectors.menu),
                menuList = $(this.options.selectors.menuList);

            menuList.toggle(!state);

            if (state) {
                menu.on('click', function (event) {
                    var close = !$(event.target).hasClass(self.options.classes.close);

                    self._toggleMenu(close);
                    menuList.toggle(close);
                });
            } else {
                self._toggleMenu(false);
                menu.off('click');
            }
        },

        /**
         * @private
         * @param {Boolean} state
         * @returns {void}
         */
        _toggleMenu: function (state) {
            $(this.options.selectors.menu).toggleClass(this.options.classes.active, state);
            $(this.options.selectors.menuContainer).toggleClass(this.options.classes.active, state);
        },

        /**
         * Init collapsible for each menu item with sub items and return them in array
         *
         * @private
         * @returns {Array}
         */
        _getAccordions: function () {
            var $container = $(this.options.selectors.accordion),
                accordions = [],
                accordionOptions = {
                    collapsible: true,
                    header: this.options.selectors.header,
                    trigger: '',
                    content: this.options.selectors.content,
                    animate: false
                },
                selector = accordionOptions.header + ',' + accordionOptions.content;

            $container.children(selector).each(function (index, elem) {
                var accordion = $(elem).collapsible(accordionOptions);

                accordions.push(accordion);
            });

            return accordions;
        }
    };

    component.init();
});
