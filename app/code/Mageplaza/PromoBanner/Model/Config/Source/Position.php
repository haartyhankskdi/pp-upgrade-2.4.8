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
 * Class Position
 *
 * @package Mageplaza\PromoBanner\Model\Config\Source
 */
class Position implements ArrayInterface
{
    const CONTENT_TOP              = 'content-top';
    const PAGE_TOP                 = 'page-top';
    const SIDEBAR_MAIN             = 'sidebar-main';
    const SIDEBAR_ADDITIONAL       = 'sidebar-additional';
    const UNDER_ADD_TO_CART_BUTTON = 'under-add-to-cart';
    const BELLOW_TOTAL_ORDER       = 'bellow-total-order';
    const SNIPPET_CODE             = 'snippet-code';
    const WIDGET                   = 'widget';
    const LEFT_FLOATING            = 'left-floating';
    const RIGHT_FLOATING           = 'right-floating';
    const POPUP                    = 'popup';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CONTENT_TOP,
                'label' => __('Top of the content')
            ],
            [
                'value' => self::PAGE_TOP,
                'label' => __('Top of the page')
            ],
            [
                'value' => self::SIDEBAR_MAIN,
                'label' => __('Main Sidebar')
            ],
            [
                'value' => self::SIDEBAR_ADDITIONAL,
                'label' => __('Additional Sidebar')
            ],
            [
                'value' => self::UNDER_ADD_TO_CART_BUTTON,
                'label' => __('Under Add To Cart button (Product Details Page)')
            ],
            [
                'value' => self::BELLOW_TOTAL_ORDER,
                'label' => __('Under Total Order (Cart View Page)')
            ],
            [
                'value' => self::LEFT_FLOATING,
                'label' => __('Left Floating')
            ],
            [
                'value' => self::RIGHT_FLOATING,
                'label' => __('Right Floating')
            ],
            [
                'value' => self::POPUP,
                'label' => __('Popup')
            ],
            [
                'value' => self::WIDGET,
                'label' => __('Customize position using Widget')
            ],
            [
                'value' => self::SNIPPET_CODE,
                'label' => __('Customize position using Snippet Code')
            ]
        ];
    }
}
