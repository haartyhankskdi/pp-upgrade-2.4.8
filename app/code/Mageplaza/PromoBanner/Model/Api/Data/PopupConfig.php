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
use Mageplaza\PromoBanner\Api\Data\PopupConfigInterface;

/**
 * Class PopupConfig
 * @package Mageplaza\PromoBanner\Model\Api\Data
 */
class PopupConfig extends DataObject implements PopupConfigInterface
{
    /**
     * @inheritDoc
     */
    public function getPopupResponsive()
    {
        return $this->getData(self::POPUP_RESPONSIVE);
    }

    /**
     * @inheritDoc
     */
    public function getPopupWidth()
    {
        return $this->getData(self::POPUP_WIDTH);
    }

    /**
     * @inheritDoc
     */
    public function getPopupHeight()
    {
        return $this->getData(self::POPUP_HEIGHT);
    }

    /**
     * @inheritDoc
     */
    public function getShowPopupCheckbox()
    {
        return $this->getData(self::SHOW_POPUP_CHECKBOX);
    }

    /**
     * @inheritDoc
     */
    public function getCheckboxLabel()
    {
        return $this->getData(self::CHECKBOX_LABEL);
    }
}
