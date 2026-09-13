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
use Mageplaza\PromoBanner\Api\Data\ConfigInterface;

/**
 * Class Config
 * @package Mageplaza\PromoBanner\Model\Api\Data
 */
class Config extends DataObject implements ConfigInterface
{
    /**
     * @inheritDoc
     */
    public function getGeneral()
    {
        return $this->getData(self::GENERAL);
    }

    /**
     * @inheritDoc
     */
    public function getSliderSetting()
    {
        return $this->getData(self::SLIDER_SETTING);
    }

    /**
     * @inheritDoc
     */
    public function getPopupSetting()
    {
        return $this->getData(self::POPUP_SETTING);
    }

    /**
     * @inheritDoc
     */
    public function getFloatingSetting()
    {
        return $this->getData(self::FLOATING_SETTING);
    }
}
