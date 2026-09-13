<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Block\Adminhtml\Order;

use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Sales\Model\Order;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Locale\CurrencyInterface;

/*
* Cardnet Payment
*/
class PaymentJs extends \Magento\Backend\Block\Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Order $order
     */
    protected $order;

    /**
     * @var Registry $_coreRegistry
     */
    protected $_coreRegistry; // phpcs:ignore

    /**
     * @var CurrencyInterface $currency
     */
    protected $currency;

    /**
     * @var array
     */
    private $paymentStatusArray = [
        '1' => 'Pending',
        '2' => 'Paid',
        '3' => 'Cancelled',
        '4' => 'Error',
        '5' => 'Refunded',
    ];

     /**
      * Constructor
      *
      * @param Context $context
      * @param Data $helper
      * @param Order $order
      * @param Registry $registry
      * @param CurrencyInterface $currency
      * @param array $data
      */
    public function __construct(
        Context $context,
        Data $helper,
        Order $order,
        Registry $registry,
        CurrencyInterface $currency,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->order = $order;
        $this->_coreRegistry = $registry;
        $this->currency = $currency;
        parent::__construct($context, $data);
    }

     /**
      * Returns Payment Data
      */
    public function getPaymentData()
    {
        $order = $this->_coreRegistry->registry('current_order');
        if (!$order || !$order->getId()) {
            return null;
        }

        return $this->helper->getPaymentByOrderId($order->getId());
    }

    /**
     * Get Currency Symbol
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        $order = $this->_coreRegistry->registry('current_order');
        if (!$order) {
            return '';
        }
        $currencyCode = $order->getOrderCurrencyCode();
        return $this->currency->getCurrency($currencyCode)->getSymbol();
    }

    /**
     * Get 3D Secure Message
     *
     * @param string|null $responseCode
     * @return string
     */
    public function get3DSSecureMessage(?string $responseCode): string
    {
        return $this->helper->getResponseMessage($responseCode) ?? '';
    }
    
    /**
     * Get Payment status
     *
     * @param int $status
     * @return string
     */
    public function getPaymentStatus($status)
    {
        return $this->paymentStatusArray[$status];
    }

    /**
     * Get card country from order billing address
     *
     * @return string
     */
    public function getCardCountry()
    {
        $order = $this->_coreRegistry->registry('current_order');
        if (!$order || !$order->getId()) {
            return 'GB';
        }

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress && $billingAddress->getCountryId()) {
            return strtoupper($billingAddress->getCountryId());
        }
        
        return 'GB';
    }

    /**
     * Return Remote Status Class
     *
     * @param string $status
     * @return string
     */
    public function getRemoteStatusClass($status)
    {
        return $this->helper->getRemoteStatusClass($status);
    }

    /**
     * Get check result HTML
     *
     * @param string $result
     * @return string
     */
    public function getCheckResult($result)
    {
        return $this->helper->getCheckResult($result);
    }

    /**
     * Get status CSS class
     *
     * @param string $status
     * @return string
     */
    public function getStatusClass($status)
    {
        return $this->helper->getStatusClass($status);
    }

    /**
     * Get payment method icon URL
     *
     * @param string $paymentMethod
     * @return string
     */
    public function getPaymentMethodIcon($paymentMethod)
    {
        return $this->helper->getPaymentMethodIcon($paymentMethod);
    }

    /**
     * Get Refund Amount
     *
     * @return float
     */
    public function getRefundAmount()
    {
        $order = $this->_coreRegistry->registry('current_order');
        if (!$order) {
            return 0;
        }
        return $order->getTotalRefunded();
    }

    /**
     * Get Amount
     *
     * @return float
     */
    public function getAmount()
    {
         $order = $this->_coreRegistry->registry('current_order');
        if (!$order) {
            return 0;
        }
        return (float) $order->getGrandTotal();
    }
}
