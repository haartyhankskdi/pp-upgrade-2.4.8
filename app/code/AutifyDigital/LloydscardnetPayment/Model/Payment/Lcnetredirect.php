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

namespace AutifyDigital\LloydscardnetPayment\Model\Payment;

use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Framework\Exception\LocalizedException;

/**
 * [Description Lcnetredirect]
 */
class Lcnetredirect extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $store_id;

    /**
     * @var string
     */
    protected $api_key;

    /**
     * @var string
     */
    protected $shared_secret;
    /**
     * @var float
     */
    protected $_maxAmount; // phpcs:ignore

    /**
     * @var float
     */
    protected $_minAmount; // phpcs:ignore

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     */
    protected $helper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $_code = "lcnetredirect"; // phpcs:ignore

    /**
     * @var bool
     */
    protected $_isGateway = true; // phpcs:ignore

    /**
     * @var bool
     */
    protected $_canCapture = true; // phpcs:ignore

    /**
     * @var bool
     */
    protected $_canRefund = true; // phpcs:ignore

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;
    
    /**
     * @var string
     */
    protected $_formBlockType = \AutifyDigital\LloydscardnetPayment\Block\Form\Lcnetredirect::class;

    /**
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \AutifyDigital\LloydscardnetPayment\Helper\Data $helper
     * @param Config $config
     * @param array $data
     *
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \AutifyDigital\LloydscardnetPayment\Helper\Data $helper,
        Config $config,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
        $this->helper = $helper;
        $this->config = $config;
    }

    /**
     * Check Is Available
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
    public function isAvailable(
        ?\Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        $mode = $this->config->getConfig('payment/basic/lloyds_mode');
        $config = $this->config->getBasicConfigurations($mode);

        $this->store_id = $config['store_id'];
        $this->api_key = $config['api_key'];
        $this->shared_secret = $config['shared_secret'];

        if ($this->api_key == '' ||
            $this->store_id == '' ||
            $this->shared_secret == ''
        ) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Online refund availability is controlled by the module configuration.
     *
     * @return bool
     */
    public function canRefund()
    {
        $refundActive = $this->config->getConfig('payment/basic/refund_active', $this->getStore());
        // Disable only when explicitly turned off; an unset/null value defaults to enabled.
        if ($refundActive !== null && (string) $refundActive === '0') {
            return false;
        }

        return parent::canRefund();
    }

    /**
     * Create Refund Offline and Online
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return \Magento\Payment\Model\Method\AbstractMethod|void
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $refund = $this->helper->doRefund($payment, $amount);
        if (!$refund) {
            throw new LocalizedException(__("Refund process failed."));
        }

        return $this;
    }
}
