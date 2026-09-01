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
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Api\Data;

/**
 * Interface GtmCartItemInterface
 * @package Mageplaza\GoogleTagManager\Api\Data
 */
interface GtmCartItemInterface
{
    const GTM_ADD_TO_CART = 'gtm_add_to_cart';
    const GA_ADD_TO_CART  = 'ga_add_to_cart';
    const FB_ADD_TO_CART  = 'fb_add_to_cart';

    /**
     * @return string
     */
    public function getGtmAddToCart();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setGtmAddToCart($value);

    /**
     * @return string
     */
    public function getGaAddToCart();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setGaAddToCart($value);

    /**
     * @return string
     */
    public function getFbAddToCart();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setFbAddToCart($value);
}
