<?php
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

namespace Mageplaza\PromoBanner\Api\Data;

/**
 * Interface PromoBannerInterface
 * @package Mageplaza\PromoBanner\Api\Data
 */
interface PromoBannerInterface
{
    const BANNER_ID          = 'banner_id';
    const NAME               = 'name';
    const STATUS             = 'status';
    const STORE_IDS          = 'store_ids';
    const CUSTOMER_GROUP_IDS = 'customer_group_ids';
    const CATEGORY           = 'category';
    const FROM_DATE          = 'from_date';
    const TO_DATE            = 'to_date';
    const PRIORITY           = 'priority';
    const TYPE               = 'type';
    const BANNER_IMAGE       = 'banner_image';
    const SLIDER_IMAGES      = 'slider_images';
    const CMS_BLOCK_ID       = 'cms_block_id';
    const CONTENT            = 'content';
    const POPUP_IMAGE        = 'popup_image';
    const POPUP_RESPONSIVE   = 'popup_responsive';
    const FLOATING_IMAGE     = 'floating_image';
    const URL                = 'url';
    const POSITION           = 'position';
    const FLOATING_POSITION  = 'floating_position';
    const PAGE               = 'page';
    const PAGE_TYPE          = 'page_type';
    const CATEGORY_IDS       = 'category_ids';
    const SHOW_PRODUCT_PAGE  = 'show_product_page';
    const AUTO_CLOSE_TIME    = 'auto_close_time';
    const AUTO_REOPEN_TIME   = 'auto_reopen_time';
    const UPDATED_AT         = 'updated_at';
    const CREATED_AT         = 'created_at';

    /**
     * @return int
     */
    public function getBannerId();

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setBannerId($value);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setName($value);

    /**
     * @return bool
     */
    public function getStatus();

    /**
     * @param int|bool|string $value
     *
     * @return $this
     */
    public function setStatus($value);

    /**
     * @return string
     */
    public function getStoreIds();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setStoreIds($value);

    /**
     * @return string
     */
    public function getCustomerGroupIds();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setCustomerGroupIds($value);

    /**
     * @return string
     */
    public function getCategory();

    /**
     * @param string|int $value
     *
     * @return $this
     */
    public function setCategory($value);

    /**
     * @return string
     */
    public function getFromDate();

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setFromDate($value);

    /**
     * @return string
     */
    public function getToDate();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setToDate($value);

    /**
     * @return int
     */
    public function getPriority();

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setPriority($value);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setType($value);

    /**
     * @return string
     */
    public function getBannerImage();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setBannerImage($value);

    /**
     * @return string
     */
    public function getSliderImages();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setSliderImages($value);

    /**
     * @return int
     */
    public function getCmsBlockId();

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setCmsBlockId($value);

    /**
     * @return string
     */
    public function getContent();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setContent($value);

    /**
     * @return string
     */
    public function getPopupImage();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setPopupImage($value);

    /**
     * @return string
     */
    public function getPopupResponsive();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setPopupResponsive($value);

    /**
     * @return string
     */
    public function getFloatingImage();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setFloatingImage($value);

    /**
     * @return string
     */
    public function getUrl();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setUrl($value);

    /**
     * @return string
     */
    public function getPosition();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setPosition($value);

    /**
     * @return string
     */
    public function getFloatingPosition();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setFloatingPosition($value);

    /**
     * @return int
     */
    public function getPage();

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setPage($value);

    /**
     * @return string
     */
    public function getPageType();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setPageType($value);

    /**
     * @return string
     */
    public function getCategoryIds();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setCategoryIds($value);

    /**
     * @return string
     */
    public function getShowProductPage();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setShowProductPage($value);

    /**
     * @return string
     */
    public function getAutoCloseTime();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setAutoCloseTime($value);

    /**
     * @return string
     */
    public function getAutoReopenTime();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setAutoReopenTime($value);

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @return string
     */
    public function getCreatedAt();
}
