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
 * Interface PopupConfigInterface
 * @package Mageplaza\PromoBanner\Api\Data
 */
interface PopupConfigInterface
{
    const POPUP_RESPONSIVE    = 'popup_responsive';
    const POPUP_WIDTH         = 'popup_width';
    const POPUP_HEIGHT        = 'popup_height';
    const SHOW_POPUP_CHECKBOX = 'show_popup_checkbox';
    const CHECKBOX_LABEL      = 'checkbox_label';

    /**
     * @return string
     */
    public function getPopupResponsive();

    /**
     * @return string
     */
    public function getPopupWidth();

    /**
     * @return string
     */
    public function getPopupHeight();

    /**
     * @return bool
     */
    public function getShowPopupCheckbox();

    /**
     * @return string
     */
    public function getCheckboxLabel();
}
