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

class CardnetApplepay extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = "cardnetapplepay"; // phpcs:ignore

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
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

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
    public function isAvailable(
        ?\Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        $mode = $this->config->getConfig('payment/basic/lloyds_mode');
        $methodIntegration = $this->config->getConfig('payment/cardnetapplepay/payment_method_integration');
        $config = $this->config->getBasicConfigurations($mode);

        $store_id = $config['store_id'];
        $shared_secret = $config['shared_secret'];
        $api_key = $config['api_key'];

        $invalid = (
            ($methodIntegration === 'onsite' && ($api_key === '' || $store_id === '')) ||
            ($methodIntegration === 'hosted' && ($store_id === '' || $shared_secret === ''))
        );

        if ($invalid) {
            return false;
        }

        if ($methodIntegration === 'hosted' && !$this->helper->getUserAgent()) {
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
    }
}
