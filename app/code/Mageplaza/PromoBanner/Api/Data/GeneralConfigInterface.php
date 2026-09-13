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
 * @category    Mageplaza
 * @package     Mageplaza_PromoBanner
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Api\Data;

/**
 * Interface GeneralConfigInterface
 * @package Mageplaza\PromoBanner\Api\Data
 */
interface GeneralConfigInterface
{
    const PROMO_CATEGORY   = 'promo_category';
    const SHOW_CLOSE_BTN   = 'show_close_btn';
    const AUTO_CLOSE_TIME  = 'auto_close_time';
    const AUTO_REOPEN_TIME = 'auto_reopen_time';

    /**
     * @return string
     */
    public function getPromoCategory();

    /**
     * @return bool
     */
    public function getShowCloseBtn();

    /**
     * @return string
     */
    public function getAutoCloseTime();

    /**
     * @return string
     */
    public function getAutoReopenTime();
}
