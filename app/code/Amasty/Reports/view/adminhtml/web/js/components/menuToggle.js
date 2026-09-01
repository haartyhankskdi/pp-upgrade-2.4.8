/**
 *  Amasty admin menu toggle functionality
 *
 *  @copyright 2009-2020 Amasty Ltd
 *  @license   https://amasty.com/license.html
 */

define([
    'jquery',
    'matchMedia'
], function ($, mediaCheck) {
    'use strict';

    $.widget('am.reportsMenuToggle', {
        options: {
            mediaBreakpoint: '(min-width: 768px)',
            classes: {
                activeClass: '-amreports-active',
                magentoActiveClassList: '_active _show'
            },
            selectors: {
                amReportsBodySelector: '.amreports-body-container',
                amReportsToggleBlock: '[data-amreports-js="menu-toggle-block"]',
                magentoMenuItem: '.menu-wrapper li[role="menu-item"]',
                magentoMenuWrapper: '.menu-wrapper'
            }
        },

        /**
         * @private
         * @returns {void}
         */
        _create: function () {
            var self = this,
                options = this.options,
                container = $(options.selectors.magentoMenuWrapper + ', ' + options.selectors.amReportsToggleBlock);

            mediaCheck({
                media: self.options.mediaBreakpoint,
                entry: function () {
                    self.element.off('click.amReports');
                    $(document).off('click.amReports');
                },
                exit: function () {
                    self.element.on('click.amReports', function () {
                        self._toggleMenu(!$(options.selectors.amReportsToggleBlock).hasClass(options.classes.activeClass));
                    });

                    $(document).on('click.amReports', function (event) {
                        if (!container.is(event.target) && !container.has(event.target).length) {
                            self._toggleMenu(false);
                        }
                    });
                }
            });
        },

        /**
         * @private
         * @param {Boolean} state
         * @returns {void}
         */
        _toggleMenu: function (state) {
            var options = this.options;

            this.element.parent().toggleClass(options.classes.activeClass, state);
            $(options.selectors.amReportsBodySelector).toggleClass(options.classes.activeClass, state);
            $(options.selectors.magentoMenuItem).removeClass(options.classes.magentoActiveClassList);
        }
    });

    return $.am.reportsMenuToggle;
});
