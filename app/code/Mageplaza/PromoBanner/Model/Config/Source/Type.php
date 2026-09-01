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

namespace Mageplaza\PromoBanner\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Type
 * @package Mageplaza\PromoBanner\Model\Config\Source
 */
class Type implements ArrayInterface
{
    const SINGLE_IMAGE = 'banner_image';
    const SLIDER       = 'slider_images';
    const POPUP        = 'popup_image';
    const FLOATING     = 'floating_image';
    const HTML_TEXT    = 'html';
    const CMS_BLOCK    = 'cms-block';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SINGLE_IMAGE, 'label' => __('Single-Image Banner')],
            ['value' => self::SLIDER, 'label' => __('Slider Banner')],
            ['value' => self::POPUP, 'label' => __('Popup Banner')],
            ['value' => self::FLOATING, 'label' => __('Floating Banner')],
            ['value' => self::HTML_TEXT, 'label' => __('HTML Text')],
            ['value' => self::CMS_BLOCK, 'label' => __('CMS Block')]
        ];
    }
}
