<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\AbandonedCart\Block\Adminhtml\Customer\Cart\Details\Edit\Tab;

class CartDetails extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Quote\Model\Quote
     **/
    protected $_quote;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     **/
    protected $_localeDate;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $data
     **/
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Quote\Model\Quote $quote,
        array $data = []
    ) {
        $this->_quote = $quote;
        $this->_localeDate = $localeDate;
        parent::__construct($context, $data);
    }

    /**
     * get quote data
     *
     * @return array
     **/
    public function getQuoteData()
    {
        $cartId = $this->getRequest()->getParam('cart_id');
        $quote = $this->_quote->getCollection()->addFieldToFilter('entity_id', ['eq' => $cartId])->getFirstItem();

        $createdTime = $this->_localeDate->date($quote->getCreatedAt())
                                        ->format('Y-m-d H:i:s');
        $updateTime = $this->_localeDate->date($quote->getUpdatedAt())
                                        ->format('Y-m-d H:i:s');
        $items = $quote->getAllItems();
        $tax = 0;
        foreach ($items as $item) {
            $tax = $tax + $item->getTaxAmount();
        }

        return $data = [
            'Customer Name' => $quote->getCustomerFirstname().' '.$quote->getCustomerLastname(),
            'Customer Email' => $quote->getCustomerEmail(),
            'Items in cart' => $quote->getItemsCount(),
            'Tax' => $quote->getQuoteCurrencyCode().' '.$tax,
            'Total' => $quote->getQuoteCurrencyCode().' '.$quote->getGrandTotal(),
            'Applied Coupon' => $quote->getCouponCode(),
            'Created At' => $createdTime,
            'Updated At' => $updateTime,
            'IP Address' => $quote->getRemoteIp(),
        ];
    }
}
