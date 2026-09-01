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

namespace Mageplaza\GoogleTagManager\Model\Api\Data;

use Magento\Framework\Model\AbstractExtensibleModel;
use Mageplaza\GoogleTagManager\Api\Data\GoogleTagManagerSearchResultsInterface;
use Mageplaza\GoogleTagManager\Api\Data\GtmCartItemInterface;

/**
 * Class GtmCartItem
 * @package Mageplaza\GoogleTagManager\Model\Api\Data
 */
class GtmCartItem extends AbstractExtensibleModel implements GtmCartItemInterface
{
    /**
     * @inheritDoc
     */
    public function getGtmAddToCart()
    {
        return $this->getData(self::GTM_ADD_TO_CART);
    }

    /**
     * @inheritDoc
     */
    public function setGtmAddToCart($value)
    {
        return $this->setData(self::GTM_ADD_TO_CART, $value);
    }

    /**
     * @inheritDoc
     */
    public function getGaAddToCart()
    {
        return $this->getData(self::GA_ADD_TO_CART);
    }

    /**
     * @inheritDoc
     */
    public function setGaAddToCart($value)
    {
        return $this->setData(self::GA_ADD_TO_CART, $value);
    }

    /**
     * @inheritDoc
     */
    public function getFbAddToCart()
    {
        return $this->getData(self::FB_ADD_TO_CART);
    }

    /**
     * @inheritDoc
     */
    public function setFbAddToCart($value)
    {
        return $this->setData(self::FB_ADD_TO_CART, $value);
    }
}
