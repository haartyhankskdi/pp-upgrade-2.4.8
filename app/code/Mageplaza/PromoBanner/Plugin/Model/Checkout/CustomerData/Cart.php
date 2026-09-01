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

namespace Mageplaza\PromoBanner\Plugin\Model\Checkout\CustomerData;

use Magento\Checkout\CustomerData\Cart as CheckoutCart;
use Magento\Quote\Model\Quote;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Model\Banner;

/**
 * Class Cart
 * @package Mageplaza\PromoBanner\Plugin\Model\Checkout\CustomerData
 */
class Cart
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var Quote|null
     */
    protected $quote = null;

    /**
     * Cart constructor.
     *
     * @param Data $helperData
     */
    public function __construct(
        Data $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * @param CheckoutCart $cart
     * @param array $result
     *
     * @return mixed
     */
    public function afterGetSectionData(CheckoutCart $cart, $result)
    {
        if (!$this->helperData->isEnabled()) {
            return $result;
        }

        $ids        = [];
        $quote      = $this->getQuote()->collectTotals();
        $address    = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $collection = $this->helperData->getPromoBannerCollection();

        foreach ($collection as $item) {
            /** @var Banner $item */
            if ($item->validate($address)) {
                $ids[] = $item->getId();
            }
        }

        $result['mppromobanner'] = [
            'items' => $ids
        ];

        return $result;
    }

    /**
     * Get active quote
     *
     * @return Quote
     */
    protected function getQuote()
    {
        if ($this->quote === null) {
            $this->quote = $this->helperData->getQuote();
        }

        return $this->quote;
    }
}
