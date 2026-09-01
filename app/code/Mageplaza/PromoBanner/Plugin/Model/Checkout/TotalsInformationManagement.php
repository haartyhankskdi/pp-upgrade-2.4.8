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

namespace Mageplaza\PromoBanner\Plugin\Model\Checkout;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\Config\Source\Page;

/**
 * Class TotalsInformationManagement
 * @package Mageplaza\PromoBanner\Plugin\Model\Checkout
 */
class TotalsInformationManagement
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var Quote|null
     */
    protected $quote = null;

    /**
     * TotalsInformationManagement constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param Data $helperData
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Data $helperData
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->helperData      = $helperData;
    }

    /**
     * @param \Magento\Checkout\Model\TotalsInformationManagement $subject
     * @param mixed ...$arg
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function beforeCalculate(
        \Magento\Checkout\Model\TotalsInformationManagement $subject,
        ...$arg
    ) {
        if (!$this->helperData->isEnabled()) {
            return $arg;
        }

        $cartId = $arg[0];
        /* @var Quote $quote */
        $quote = $this->quoteRepository->get($cartId);

        $extensionAttributes = $quote->getExtensionAttributes();
        if ($extensionAttributes && !$quote->isVirtual() && $extensionAttributes->getShippingAssignments()) {
            /** @var ShippingAssignmentInterface[] $shippingAssignments */
            $shippingAssignments = $extensionAttributes->getShippingAssignments();

            if (!empty($shippingAssignments)) {
                $shippingAssignments[0]->getShipping()->setMethod($quote->getShippingAddress()->getShippingMethod());
            }

            $this->quoteRepository->save($quote);
        }

        return $arg;
    }

    /**
     * @param \Magento\Checkout\Model\TotalsInformationManagement $subject
     * @param TotalsInterface $result
     *
     * @return TotalsInterface $result
     */
    public function afterCalculate(\Magento\Checkout\Model\TotalsInformationManagement $subject, $result)
    {
        if (!$this->helperData->isEnabled()) {
            return $result;
        }

        if ($result->getExtensionAttributes() !== null) {
            $result->getExtensionAttributes()->setMpPromoBanner($this->getPromoBannerValidate());
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getPromoBannerValidate()
    {
        $ids        = [];
        $collection = $this->helperData->getPromoBannerCollection();
        $collection->addFieldToFilter(['page', 'page_type'], [['eq' => 0], ['finset' => Page::CART_PAGE]]);
        if ($this->helperData->isEnabled() && $collection && $collection->getSize()) {
            $quote   = $this->getQuote()->collectTotals();
            $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

            foreach ($collection as $item) {
                /** @var Banner $item */
                if ($item->validate($address)) {
                    $ids[] = $item->getId();
                }
            }
        }

        return $ids;
    }

    /**
     * Get active quote
     *
     * @return Quote
     */
    protected function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->helperData->getQuote();
        }

        return $this->quote;
    }
}
