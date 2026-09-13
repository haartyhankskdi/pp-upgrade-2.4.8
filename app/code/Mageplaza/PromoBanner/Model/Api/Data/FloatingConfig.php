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
use Mageplaza\PromoBanner\Api\Data\FloatingConfigInterface;

/**
 * Class FloatingConfig
 * @package Mageplaza\PromoBanner\Model\Api\Data
 */
class FloatingConfig extends DataObject implements FloatingConfigInterface
{
    /**
     * @inheritDoc
     */
    public function getFloatWidth()
    {
        return $this->getData(self::FLOAT_WIDTH);
    }

    /**
     * @inheritDoc
     */
    public function getFloatHeight()
    {
        return $this->getData(self::FLOAT_HEIGHT);
    }
}
