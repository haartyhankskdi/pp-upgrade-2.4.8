/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_GoogleTagManager
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

define([
    'jquery',
    'mage/template'
], function ($, mageTemplate) {
    'use strict';

    return function (config) {
        var attributeOption   = {
                element: config.element,
                table: config.table,
                rendered: 0,
                template: mageTemplate(config.template),
                add: function (data, render) {
                    var isNewOption = false,
                        element, d, _id;

                    if (typeof data.id == 'undefined') {
                        d           = new Date();
                        _id         = d.getTime() + '_' + d.getMilliseconds();
                        data        = {'id': _id};
                        isNewOption = true;
                    }

                    element = this.template({data: data});

                    if (isNewOption) {
                        this.enableNewOptionDeleteButton(data.id);
                    }
                    this.elements += element;

                    if (render) {
                        this.render();
                    }
                    $('#' + this.element + '-manage-options-panel .validation label.mage-error').remove();
                },

                remove: function (event) {
                    var element,
                        elementFlags;

                    element = $(event.target).closest('tr');

                    element.parents().each(
                        function () {
                            if ($(this).hasClass('option-row')) {
                                element = $(this);
                                throw $break;
                            } else if ($(this).hasClass('box')) {
                                throw $break;
                            }
                        }
                    );

                    if (element) {
                        elementFlags = element.find('.delete-flag');

                        if (elementFlags[0]) {
                            elementFlags[0].value = 1;
                        }

                        element.addClass('no-display');
                        element.addClass('template');
                        element.hide();
                    }
                },

                enableNewOptionDeleteButton: function (id) {
                    $('#' + this.element + '-delete_button_container_' + id + ' button').each(
                        function () {
                            $(this).prop('disabled', false);
                            $(this).removeClass('disabled');
                        }
                    );
                },

                render: function () {
                    $('[data-role=' + this.element + '-options-container]').append(this.elements);
                    this.elements = '';
                },

                renderWithDelay: function (element, data, from, step, delay) {
                    var arrayLength = data.length,
                        len;

                    for (len = from + step; from < len && from < arrayLength; from++){
                        this.add(data[from]);
                    }
                    this.render();

                    data.each(function (value) {
                        $('select[name="groups[analyticstag][fields][custom_' + element + '][value][option][value][' + value.id + '][value]"]').val(value.value);
                        $('select[name="groups[analyticstag][fields][custom_' + element + '][value][option][value][' + value.id + '][index]"]').val(value.index);
                    });

                    if (from === arrayLength) {
                        this.rendered = 1;
                        $('body').trigger('processStop');

                        return true;
                    }
                    setTimeout(this.renderWithDelay.bind(this, data, from, step, delay), delay);
                },

                ignoreValidate: function () {
                    $('#config-edit-form').data('validator').settings.forceIgnore = '.ignore-validate input, ' +
                        '.ignore-validate select, ' + '.ignore-validate textarea';
                },

                showDimensionsAndMetrics: function () {
                    if ($('#googletagmanager_analyticstag_enabled').val() === '0') {
                        $('#row_mageplaza_gtm_dimensions').hide();
                        $('#row_mageplaza_gtm_metrics').hide();
                    } else {
                        $('#row_mageplaza_gtm_dimensions').show();
                        $('#row_mageplaza_gtm_metrics').show();
                    }
                }
            },
            addNewOptButtonEl = $('#' + config.element + '-add_new_option_button');

        if (addNewOptButtonEl.length) {
            addNewOptButtonEl.on('click', function () {
                attributeOption.add({}, true);
            });
        }
        $('#' + config.element + '-manage-options-panel').on('click', '.delete-option', function (event) {
            attributeOption.remove(event);
        });

        attributeOption.ignoreValidate();

        if (attributeOption.rendered) {
            return false;
        }
        $('body').trigger('processStart');
        attributeOption.renderWithDelay(config.element, config.attributesData, 0, 100, 300);
        attributeOption.showDimensionsAndMetrics();

        $('#googletagmanager_analyticstag_enabled').change(function () {
            attributeOption.showDimensionsAndMetrics();
        });
    };
});
