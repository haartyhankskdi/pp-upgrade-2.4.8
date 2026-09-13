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

namespace AutifyDigital\LloydscardnetPayment\Block;

use AutifyDigital\LloydscardnetPayment\Model\ConfigProvider;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;

class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     * @param Data $helper
     * @param ConfigProvider $configProvider
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Data $helper,
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $context->getUrlBuilder();
        parent::__construct($context, $data);
    }

    /**
     * Retrieve cardnet google pay config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->configProvider->getConfig()['payment']['cardnetgooglepay'];
    }

    /**
     * Retrieve cardnet google pay config
     *
     * @return array
     */
    public function getApplePayConfig()
    {
        return $this->configProvider->getConfig()['payment']['cardnetapplepay'];
    }

    /**
     * Retrieve current quote id
     *
     * @return int
     */
    public function getQuoteId()
    {
        return $this->helper->getOrderSession()->getQuoteId();
    }

    /**
     * Retrieve price configuration for the current quote.
     *
     * This includes details such as subtotal, subtotal with discount,
     * discount amount, tax amount, grand total, shipping amount,
     * shipping tax amount, and discount description.
     *
     * @return array An associative array containing the price configuration.
     */
    public function getPriceConfig()
    {
        $quote = $this->helper->getOrderSession()->getQuote();

        return [
            'subtotal' => $quote->getSubtotal(),
            'subtotalWithDiscount' => $quote->getSubtotalWithDiscount(),
            'discountAmount' => $quote->getSubtotal() > 0 ? $quote->getSubtotal() - $quote->getGrandTotal() : 0,
            'taxAmount' => $quote->getTaxAmount(),
            'grandTotal' => $quote->getGrandTotal(),
            'shippingAmount' => $quote->getShippingAmount(),
            'shippingTaxAmount' => $quote->getShippingTaxAmount(),
            'discountDescription' => $quote->getDiscountDescription() ?: 'No discount applied'
        ];
    }

    /**
     * Retrieve the base URL of the store
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->urlBuilder->getBaseUrl();
    }

    /**
     * Encodes a given PHP variable into a JSON string.
     *
     * @param mixed $data
     * @return string
     */
    public function getJsonEncode($data)
    {
        return $this->helper->getJsonEncode($data);
    }
}
