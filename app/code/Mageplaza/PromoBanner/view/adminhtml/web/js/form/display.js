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
 * @category    Mageplaza
 * @package     Mageplaza_PromoBanner
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

define([
    'jquery'
], function ($) {
    "use strict";
    var type           = $('#mppromobanner_type'),
        content        = $('#mppromobanner_content'),
        position       = $('#mppromobanner_position'),
        optionPosition = position.find('option');

    $.widget('mageplaza.promobannerDisplay', {
        /**
         * This method constructs a new widget.
         * @private
         */
        _create: function () {
            this.initListener();
        },

        initListener: function () {
            type.on('change', function () {
                if (type.val() === 'html') {
                    content.parents('.field-content').show();
                } else {
                    content.parents('.field-content').hide();
                }

                $.each(optionPosition, function (index, value) {
                    var optionVal     = value.value,
                        isOptionFloat = optionVal === 'left-floating' || optionVal === 'right-floating',
                        isFloatValue  = position.val() === 'left-floating' || position.val() === 'right-floating';

                    if (type.val() !== 'floating_image' && type.val() !== 'popup_image') {
                        optionPosition[index].hidden = isOptionFloat || optionVal === 'popup';

                        if (isFloatValue || position.val() === 'popup') {
                            position.val('content-top');
                        }
                    } else if (type.val() === 'floating_image') {
                        optionPosition[index].hidden = !isOptionFloat;

                        if (!isFloatValue) {
                            position.val('right-floating');
                        }
                    } else {
                        optionPosition[index].hidden = optionVal !== 'popup';
                        position.val('popup');
                    }
                });
            });

            $('#mppromobanner_preview_banner').on('click', function () {
                if (type.val() === 'html' || type.val() === 'cms-block') {
                    $('#preview_content').val($('#mppromobanner_content').val());
                    $('#preview_cms').val($('#mppromobanner_cms_block_id').val());
                    $('#preview_type').val(type.val());
                    $('#mppromobanner_preview_form').submit();
                }
            });

            type.trigger("change");
        }
    });

    return $.mageplaza.promobannerDisplay;
});