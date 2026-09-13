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

namespace Mageplaza\PromoBanner\Model\Api\Data;

use Magento\Framework\DataObject;
use Mageplaza\PromoBanner\Api\Data\GeneralConfigInterface;

/**
 * Class GeneralConfig
 * @package Mageplaza\PromoBanner\Model\Api\Data
 */
class GeneralConfig extends DataObject implements GeneralConfigInterface
{
    /**
     * @inheritDoc
     */
    public function getPromoCategory()
    {
        return $this->getData(self::PROMO_CATEGORY);
    }

    /**
     * @inheritDoc
     */
    public function getShowCloseBtn()
    {
        return $this->getData(self::SHOW_CLOSE_BTN);
    }

    /**
     * @inheritDoc
     */
    public function getAutoCloseTime()
    {
        return $this->getData(self::AUTO_CLOSE_TIME);
    }

    /**
     * @inheritDoc
     */
    public function getAutoReopenTime()
    {
        return $this->getData(self::AUTO_REOPEN_TIME);
    }
}
