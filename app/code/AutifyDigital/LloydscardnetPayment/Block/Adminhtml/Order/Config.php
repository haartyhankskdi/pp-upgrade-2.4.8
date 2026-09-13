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

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Backend\Model\Session\Quote as QuoteSession;
use Magento\Customer\Model\Session as CustomerSession;
use AutifyDigital\LloydscardnetPayment\Model\ConfigProvider;
use Magento\Sales\Api\OrderRepositoryInterface;

class Config extends Template
{
    /**
     * @var ConfigProvider
     */
    protected $configHelper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var QuoteSession
     */
    protected $quoteSession;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Data $helper
     * @param ConfigProvider $configHelper
     * @param QuoteSession $quoteSession
     * @param CustomerSession $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        ConfigProvider $configHelper,
        QuoteSession $quoteSession,
        CustomerSession $customerSession,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->configHelper = $configHelper;
        $this->quoteSession = $quoteSession;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get environment mode
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->helper->getMode();
    }

    /**
     * Get payment configuration for admin
     *
     * @return array
     */
    public function getPaymentConfig(): array
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $customerId = 0;

        if ($orderId) {
            try {
                $order = $this->orderRepository->get($orderId);
                $customerId = $order->getCustomerId() ?? 0;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $customerId = 0;
            }
        }

        $allowedBrands = (string) $this->helper->getConfig('payment/lcnetpaymentjs/supported_networks');

        return [
            'lcnetpaymentjs' => [
                'title' => $this->helper->getConfig('payment/lcnetpaymentjs/title'),
                'placeholder_card' => $this->helper->getConfig('payment/lcnetpaymentjs/placeholder_card'),
                'placeholder_cvv' => $this->helper->getConfig('payment/lcnetpaymentjs/placeholder_cvv'),
                'placeholder_name' => $this->helper->getConfig('payment/lcnetpaymentjs/placeholder_name'),
                'allowed_brands' => $allowedBrands === ''
                    ? []
                    : array_values(array_filter(array_map('trim', explode(',', $allowedBrands))))
            ]
        ];
    }

    /**
     * Get PaymentJS URL
     *
     * @return string
     */
    public function getPaymentJsUrl(): string
    {
        return $this->getMode() === 'Live'
            ? 'https://docs.paymentjs.firstdata.com/lib/prod/client-2.0.0.js'
            : 'https://docs.paymentjs.firstdata.com/lib/uat/client-2.0.0.js';
    }

    /**
     * Retrieve Library
     *
     * @return String
     */
    public function getLibrary()
    {
        return $this->helper->getLibrary();
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
