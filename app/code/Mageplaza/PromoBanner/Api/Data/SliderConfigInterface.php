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
 * Interface SliderConfigInterface
 * @package Mageplaza\PromoBanner\Api\Data
 */
interface SliderConfigInterface
{
    const SHOW_BUTTONS   = 'show_buttons';
    const CHANGE_TIME   = 'change_time';

    /**
     * @return bool
     */
    public function getShowButtons();

    /**
     * @return string
     */
    public function getChangeTime();
}
