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
 * Class Frequency
 *
 * @package Mageplaza\PromoBanner\Model\Config\Source
 */
class Frequency implements ArrayInterface
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
     * Auto reopen options are calculated in seconds
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'infinite', 'label' => __('No Limitation')],
            ['value' => 5 * 60, 'label' => __('Every 5 minutes')],
            ['value' => 10 * 60, 'label' => __('Every 10 minutes')],
            ['value' => 30 * 60, 'label' => __('Every 30 minutes')],
            ['value' => 60 * 60, 'label' => __('Every 1 hour')],
            ['value' => 3 * 60 * 60, 'label' => __('Every 3 hours')],
            ['value' => 6 * 60 * 60, 'label' => __('Every 6 hours')],
            ['value' => 12 * 60 * 60, 'label' => __('Every 12 hours')],
            ['value' => 1 * 24 * 60 * 60, 'label' => __('Everyday')],
            ['value' => 3 * 24 * 60 * 60, 'label' => __('Every 3 days')],
            ['value' => 7 * 24 * 60 * 60, 'label' => __('Every week')],
            ['value' => 14 * 24 * 60 * 60, 'label' => __('Every 2 weeks')],
            ['value' => 30 * 24 * 60 * 60, 'label' => __('Every month')],
            ['value' => 'never', 'label' => __('Never')],
        ];
    }
}
