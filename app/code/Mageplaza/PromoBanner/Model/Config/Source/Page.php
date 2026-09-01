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

use Magento\Framework\Module\Manager;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Page
 *
 * @package Mageplaza\PromoBanner\Model\Config\Source
 */
class Page implements ArrayInterface
{
    const HOME_PAGE     = 'cms_index_index';
    const CART_PAGE     = 'checkout_cart_index';
    const CHECKOUT_PAGE = 'checkout_index_index';
    const OSC_PAGE      = 'onestepcheckout_index_index';

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * Page constructor.
     *
     * @param Manager $moduleManager
     */
    public function __construct(Manager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $option = [
            [
                'value' => '',
                'label' => __('-- Not Select --')
            ],
            [
                'value' => self::HOME_PAGE,
                'label' => __('Home Page')
            ],
            [
                'value' => self::CART_PAGE,
                'label' => __('Shopping Cart Page')
            ],
            [
                'value' => self::CHECKOUT_PAGE,
                'label' => __('Checkout Page')
            ]
        ];

        if ($this->moduleManager->isEnabled('Mageplaza_Osc')) {
            $option[] =
                [
                    'value' => self::OSC_PAGE,
                    'label' => __('One Step Checkout Page')
                ];
        }

        return $option;
    }
}
