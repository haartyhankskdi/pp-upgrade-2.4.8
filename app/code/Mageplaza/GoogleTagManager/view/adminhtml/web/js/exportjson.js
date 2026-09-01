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
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

define(["jquery"], function ($) {

    return function (config) {
        var ajaxUrl                = config.ajaxUrl,
            downloadUrl            = config.downloadUrl,
            exportButton           = $('#googletagmanager_export_gtm_export_button'),
            measurementEle         = $('#googletagmanager_export_gtm_export_ga4_measurement_id'),
            adsConversionIdEle     = $('#googletagmanager_export_gtm_export_ads_conversion_conversion_id'),
            adsConversionLabelEle  = $('#googletagmanager_export_gtm_export_ads_conversion_conversion_label'),
            adsRemarketingIdEle    = $('#googletagmanager_export_gtm_export_ads_remarketing_conversion_id'),
            adsRemarketingLabelEle = $('#googletagmanager_export_gtm_export_ads_remarketing_conversion_label');

        exportButton.click(function () {
            var measurementId       = measurementEle.val(),
                adsConversionId     = adsConversionIdEle.val(),
                adsConversionLabel  = adsConversionLabelEle.val(),
                adsRemarketingId    = adsRemarketingIdEle.val(),
                adsRemarketingLabel = adsRemarketingLabelEle.val(),
                errorMessageEle     = $('.message-error');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    measurementId: measurementId,
                    adsConversionId: adsConversionId,
                    adsConversionLabel: adsConversionLabel,
                    adsRemarketingId: adsRemarketingId,
                    adsRemarketingLabel: adsRemarketingLabel
                },
                success: function (data) {
                    if (data.status) {
                        errorMessageEle.hide();
                        window.location = downloadUrl;
                    } else {
                        errorMessageEle.show();
                        errorMessageEle.html(data.message);
                    }
                }
            });
        });
    }
});
