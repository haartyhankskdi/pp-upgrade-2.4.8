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
 * Class PopupResponsive
 * @package Mageplaza\PromoBanner\Model\Config\Source
 */
class PopupResponsive implements ArrayInterface
{
    const FULL_SCREEN = 'full_screen';
    const CENTER      = 'center';
    const CONFIG      = 'config';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::CENTER, 'label' => __('Center Popup')],
            ['value' => self::FULL_SCREEN, 'label' => __('Full Screen Popup')]
        ];
    }

    /**
     * @return array
     */
    public function toFormOptionArray()
    {
        $option = [
            ['value' => self::CONFIG, 'label' => __('Use Config')]
        ];

        return array_merge($option, $this->toOptionArray());
    }
}
