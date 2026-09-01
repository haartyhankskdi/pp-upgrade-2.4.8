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
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Model\Config\Source;

use Mageplaza\GoogleTagManager\Model\Config\Source\AbstractSource;

/**
 * Class Position
 * @package Mageplaza\GoogleTagManager\Model\Config\Source
 */
class Position extends AbstractSource
{
    const SELECT   = 'select';
    const RELATED  = 'related';
    const UPSELL   = 'up_sell';
    const COSSSELL = 'cross_sell';
    const SEARCH   = 'search';

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::SELECT   => __('-- Please select --'),
            self::RELATED  => __('Related Product'),
            self::UPSELL   => __('Up-sell Product'),
            self::COSSSELL => __('Cross-sell Product'),
            self::SEARCH   => __('Search Result Page'),
        ];
    }
}
