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
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

define(
    [
        'jquery',
        'underscore',
        'uiRegistry',
        'mage/template',
        'Magento_Customer/js/customer-data',
        'mageplaza/core/jquery/popup'
    ], function ($, _, registry, mageTemplate, customerData) {
        'use strict';

        $.widget(
            'mageplaza.promobanner', {
                _create: function () {
                    var self = this;

                    registry.async('localStorage')(function (storage) {
                        this.storage = storage;
                        this._loadAjax();
                        if (window.checkoutConfig !== undefined) {
                            if (this.options.isCartPage === 'checkout_index_index') {
                                this.initPromoBanner(customerData.get('cart')()['mppromobanner']);
                            }
                            require(['Magento_Checkout/js/model/quote'], function (quote) {
                                // Update banner in cart page based on estimate shipping
                                quote.totals.subscribe(function (totals) {
                                    var bannerCache = {};

                                    if (totals.extension_attributes && totals.extension_attributes.mp_promo_banner) {
                                        bannerCache.items = totals.extension_attributes.mp_promo_banner;

                                        if (bannerCache.items.length) {
                                            self.initPromoBanner(bannerCache);
                                        }
                                    }
                                });
                            });
                        }
                    }.bind(this));
                },

                /**
                 * Show Promo banner
                 *
                 * @param response
                 */
                initPromoBanner: function (response) {
                    var template,
                        self       = this,
                        collection = this.options.collection.items,
                        popupLink  = $('.open-popup-link');

                    $.each(collection, function (index, value) {
                        var bannerId    = 'mppromobanner-banner-' + value.banner_id,
                            bannerClass = '.' + bannerId,
                            bannerType  = value.type,
                            templateId  = '#mppromobanner-template-floating-' + value.position;

                        if ($.inArray(value.banner_id, response.items) === -1) {
                            this.element.find(bannerClass).remove();
                            return true;
                        }

                        if (this.element.find(bannerClass).length) {
                            if (bannerType === 'slider_images') {
                                this.initDataStorage(
                                    bannerId,
                                    value.type,
                                    value.auto_close_time,
                                    value.auto_reopen_time
                                );
                            }
                            return !(bannerType === 'floating_image' || bannerType === 'popup_image');
                        }

                        if (bannerType === 'floating_image') {
                            this.element.find('.mppromobanner-floating').remove();
                            template = mageTemplate(
                                templateId,
                                {
                                    data: {
                                        id: value.banner_id,
                                        url: value.url,
                                        image: value.floating_image,
                                        url_image: this.options.media_path + value.floating_image
                                    }
                                }
                            );
                            this.element.append(template);
                        } else if (bannerType === 'popup_image') {
                            template = mageTemplate(
                                '#mppromobanner_popup_template',
                                {
                                    data: {
                                        id: value.banner_id,
                                        url: value.url,
                                        image: value.popup_image,
                                        url_image: this.options.media_path + value.popup_image,
                                        responsive: value.popup_responsive
                                    }
                                }
                            );

                            this.element.append(template);
                            popupLink.attr('href', '#mppromobanner-banner-' + value.banner_id);
                            popupLink.magnificPopup({
                                type: 'inline',
                                midClick: true,
                                showCloseBtn: self.options.showCloseBtn === '1',
                                callbacks: {
                                    open: function () {
                                        var timeOut;

                                        if (value.auto_close_time !== 'never') {
                                            clearTimeout($(bannerClass).data('hideInterval'));
                                            timeOut = setTimeout(function () {
                                                popupLink.magnificPopup('close');
                                            }, parseInt(value.auto_close_time, 10) * 1000);

                                            $(bannerClass).data('hideInterval', timeOut);
                                        }
                                    },
                                    close: function () {
                                        if ($('#mppromobanner-popup-checkbox').is(':checked')) {
                                            self.updateDataAfterClose(bannerId);
                                        }
                                    }
                                }
                            });
                        } else if (bannerType !== 'slider_images') {
                            this.element.append(value.content);
                        }
                        this.initDataStorage(bannerId, value.type, value.auto_close_time, value.auto_reopen_time);

                        if (bannerType === 'floating_image' || bannerType === 'popup_image') {
                            return false;
                        }
                    }.bind(this));

                    this.closePromoBanner();
                    this.element.trigger('mpPromoBannerUpdated');
                },

                /**
                 * Initialize promobanner storage data
                 *
                 * @param bannerId
                 * @param type
                 * @param closeTime
                 * @param openTime
                 */
                initDataStorage: function (bannerId, type, closeTime, openTime) {
                    var storageData = this.storage.get('mppromobannerData'),
                        bannerClass = $('.' + bannerId),
                        self        = this,
                        bannerType, bannerStatus, rangeCloseTime;

                    if (storageData) {
                        if (!(storageData[bannerId]
                            && storageData[bannerId].rangeCloseTime === closeTime
                            && storageData[bannerId].rangeReopenTime === openTime)
                        ) {
                            storageData = this.updateDataStorage(storageData, bannerId, type, closeTime, openTime);
                        }
                        if (storageData[bannerId]) {
                            bannerType   = storageData[bannerId].type;
                            bannerStatus = storageData[bannerId].status;

                            if (!bannerStatus && bannerType !== 'popup_image' && bannerType !== 'slider_images') {
                                $(bannerClass).hide();
                            }
                            if (bannerStatus && bannerType === 'popup_image') {
                                $('.open-popup-link').trigger('click');
                            }
                            if (bannerStatus && bannerType === 'slider_images') {
                                $('.' + storageData[bannerId].banner_id).show();
                            }
                        }
                    } else {
                        storageData = this.updateDataStorage({}, bannerId, type, closeTime, openTime);
                        if (type === 'popup_image') {
                            $('.open-popup-link').trigger('click');
                        }
                        if (type === 'slider_images') {
                            $('.' + storageData[bannerId].banner_id).show();
                        }
                    }

                    // Check the display status of the banner at the previous time
                    // check config never auto close/reopen and reopen one time
                    if (storageData[bannerId].status) {
                        rangeCloseTime = storageData[bannerId].rangeCloseTime;
                        if (rangeCloseTime !== 'never' && bannerType !== 'popup_image') {
                            this.element.on('mpPromoBannerUpdated', function () {
                                if (bannerClass.is(':visible')) {
                                    self.setAutoTime(bannerId, 'hideInterval', rangeCloseTime * 1000);
                                }
                            });
                        }
                    } else if (
                        storageData[bannerId].rangeReopenTime !== 'infinite'
                        && storageData[bannerId].rangeReopenTime !== 'never'
                    ) {
                        this.setAutoTime(
                            bannerId,
                            'showInterval',
                            storageData[bannerId].autoReopenTime - new Date().getTime()
                        );
                    }
                },

                /**
                 * Update promo banner data to localstorage
                 *
                 * @param data
                 * @param bannerId
                 * @param type
                 * @param closeTime
                 * @param openTime
                 * @returns {*}
                 */
                updateDataStorage: function (data, bannerId, type, closeTime, openTime) {
                    if (data[bannerId]) {
                        data[bannerId].rangeCloseTime  = closeTime;
                        data[bannerId].rangeReopenTime = openTime;
                    } else {
                        data[bannerId] = {
                            banner_id: bannerId,
                            type: type,
                            status: true,
                            count: 1,
                            rangeCloseTime: closeTime,
                            rangeReopenTime: openTime,
                            autoReopenTime: 0
                        };
                    }

                    this.storage.set('mppromobannerData', data);

                    return data;
                },

                /**
                 * Event customer close promo banner
                 */
                closePromoBanner: function () {
                    var bannerClass,
                        self = this;

                    $('.mppromobanner-close').on('click', function () {
                        bannerClass = $(this).parent();
                        bannerClass.hide();
                        clearTimeout(bannerClass.data('hideInterval'));
                        bannerClass = bannerClass.attr('class').split(' ');
                        self.updateDataAfterClose(bannerClass[0]);
                    });
                },

                /**
                 * Update storage data after click close button
                 *
                 * @param bannerId
                 */
                updateDataAfterClose: function (bannerId) {
                    var storageData     = this.storage.get('mppromobannerData'),
                        rangeReopenTime = storageData[bannerId].rangeReopenTime;

                    if (rangeReopenTime !== 'infinite' && rangeReopenTime !== 'never') {
                        storageData[bannerId].status         = false;
                        storageData[bannerId].autoReopenTime = new Date().getTime() + rangeReopenTime * 1000;
                        this.setAutoTime(
                            bannerId,
                            'showInterval',
                            storageData[bannerId].autoReopenTime - new Date().getTime()
                        );
                    } else {
                        storageData[bannerId].status = rangeReopenTime === 'infinite';
                    }
                    this.storage.set('mppromobannerData', storageData);
                },

                setAutoTime: function (bannerId, type, time) {
                    var timeOut,
                        self        = this,
                        bannerClass = $('.' + bannerId);

                    clearTimeout(bannerClass.data(type));
                    if (type === 'hideInterval') {
                        timeOut = setTimeout(function () {
                            bannerClass.hide();
                        }, parseInt(time, 10));
                    } else {
                        timeOut = setTimeout(function () {
                            var storageData = self.storage.get('mppromobannerData');

                            storageData[bannerId].type === 'popup_image'
                                ? $('.open-popup-link').trigger('click')
                                : bannerClass.show();
                            storageData[bannerId].status = true;
                            self.storage.set('mppromobannerData', storageData);
                            self.element.trigger('mpPromoBannerUpdated');
                        }, parseInt(time, 10));
                    }

                    bannerClass.data(type, timeOut);
                },

                _loadAjax: function () {
                    var self = this,
                        cartObj;

                    $(document).ajaxComplete(function (event, xhr, settings) {
                        if (settings.url.indexOf("sections=cart") > 0) {
                            cartObj = xhr.responseJSON;
                            if (!cartObj.cart.mppromobanner || !cartObj.cart.mppromobanner.items.length) {
                                return;
                            }

                            self.initPromoBanner(cartObj.cart.mppromobanner);
                        }
                    });
                }
            }
        );

        return $.mageplaza.promobanner;
    }
);
