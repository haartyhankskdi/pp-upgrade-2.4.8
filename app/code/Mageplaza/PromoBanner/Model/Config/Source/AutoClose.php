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
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class AutoClose
 *
 * @package Mageplaza\PromoBanner\Model\Config\Source
 */
class AutoClose implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArrayConfig()
    {
        $option   = $this->toOptionArray();
        $option[] = ['value' => 'use_config', 'label' => __('Use Config')];

        return $option;
    }

    /**
     * Auto close options are calculated in seconds
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'never', 'label' => __('Never')],
            ['value' => 10, 'label' => __('10 seconds')],
            ['value' => 15, 'label' => __('15 seconds')],
            ['value' => 30, 'label' => __('30 seconds')],
            ['value' => 45, 'label' => __('45 seconds')],
            ['value' => 60, 'label' => __('1 minute')],
            ['value' => 2 * 60, 'label' => __('2 minute')],
            ['value' => 5 * 60, 'label' => __('5 minutes')],
            ['value' => 10 * 60, 'label' => __('10 minutes')],
            ['value' => 15 * 60, 'label' => __('15 minutes')],
            ['value' => 30 * 60, 'label' => __('30 minutes')],
            ['value' => 45 * 60, 'label' => __('45 minutes')],
            ['value' => 1 * 60 * 60, 'label' => __('1 hour')]
        ];
    }
}
