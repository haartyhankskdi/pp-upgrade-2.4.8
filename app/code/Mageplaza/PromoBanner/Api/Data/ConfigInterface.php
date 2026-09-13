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
 * Interface ConfigInterface
 * @package Mageplaza\PromoBanner\Api\Data
 */
interface ConfigInterface
{
    const GENERAL          = 'general';
    const SLIDER_SETTING   = 'slider_setting';
    const POPUP_SETTING    = 'popup_setting';
    const FLOATING_SETTING = 'floating_setting';

    /**
     * @return \Mageplaza\PromoBanner\Api\Data\GeneralConfigInterface
     */
    public function getGeneral();

    /**
     * @return \Mageplaza\PromoBanner\Api\Data\SliderConfigInterface
     */
    public function getSliderSetting();

    /**
     * @return \Mageplaza\PromoBanner\Api\Data\PopupConfigInterface
     */
    public function getPopupSetting();

    /**
     * @return \Mageplaza\PromoBanner\Api\Data\FloatingConfigInterface
     */
    public function getFloatingSetting();
}
