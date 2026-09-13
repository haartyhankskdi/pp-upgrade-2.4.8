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

namespace Mageplaza\PromoBanner\Block\Banner;

use Mageplaza\PromoBanner\Block\PromoBanner;
use Mageplaza\PromoBanner\Model\Config\Source\PopupResponsive;
use Mageplaza\PromoBanner\Model\Config\Source\Type;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;

/**
 * Class Popup
 * @package Mageplaza\PromoBanner\Block\Banner
 */
class Popup extends PromoBanner
{
    /**
     * @return Collection
     */
    public function getPromoBannerCollection()
    {
        $collection = parent::getPromoBannerCollection();
        $collection->addFieldToFilter('type', Type::POPUP);
        foreach ($collection as $item) {
            if ($item->getPopupResponsive() === PopupResponsive::CONFIG) {
                $item->setPopupResponsive($this->helperData->getPopupResponsive());
            }

            $this->setAutoTime($item);
        }

        return $collection;
    }

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function getConfigPopup($code)
    {
        return $this->helperData->getConfigPopup($code);
    }
}
