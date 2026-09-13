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

use AutifyDigital\LloydscardnetPayment\Helper\Data;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Lloyds Cardnet Payment Method Model
 */
class Lcnetpaymentjs extends AbstractMethod
{
    public const PAYMENT_ACTION_ORDER = 'order';
    public const PAYMENT_ACTION_AUTHORIZE = 'authorize';
    public const PAYMENT_ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';
    
    /**
     * @var string
     */
    protected $_code = 'lcnetpaymentjs';

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
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canCancel = true;

    /**
     * @var bool
     */
    protected $_canReviewPayment = false;

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
     * @var string
     */
    protected $_formBlockType = \AutifyDigital\LloydscardnetPayment\Block\Form\Lcnetpaymentjs::class;

    /**
     * @var string
     */
    protected $storeId;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $sharedSecret;

    /**
     * @var float
     */
    protected $_maxAmount;

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
     * @var Curl
     */
    protected $curl;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

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
     * @param Curl $curl
     * @param OrderRepositoryInterface $orderRepository
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
        Curl $curl,
        OrderRepositoryInterface $orderRepository,
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
        $this->curl = $curl;
        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
        $this->helper = $helper;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
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

        $this->store_id = $config['store_id'] ?? '';
        $this->api_key = $config['api_key'] ?? '';
        $this->shared_secret = $config['shared_secret'] ?? '';

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

    /**
     * Get configured payment action
     *
     * @return string|null
     */
    public function getConfigPaymentAction(): ?string
    {
        return $this->helper->getConfig('payment/lcnetpaymentjs/payment_action');
    }

    /**
     * Authorize payment
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $order = $payment->getOrder();

        // For authorize-only transactions, we need to check if payment was already processed
        // by the frontend controller to avoid setting pending status unnecessarily
        $authTxnId = $payment->getLastTransId();

        if ($authTxnId) {
            // Transaction already exists, don't set as pending to avoid payment review
            $payment->setIsTransactionClosed(false);
            $payment->setIsTransactionPending(false);
        } else {
            // No transaction yet, set as pending for processing
            $payment->setIsTransactionClosed(false);
            $payment->setIsTransactionPending(true);
        }

        return $this;
    }

    /**
     * Capture payment
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        if ($this->getConfigPaymentAction() === self::PAYMENT_ACTION_AUTHORIZE_CAPTURE) {
            // For authorize_capture, payment is already processed
            $payment->setIsTransactionPending(false);
            return $this;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $authTxnId = $payment->getLastTransId();

        if (!$authTxnId) {
            // Check if this is a stuck payment in review status
            if ($order->getState() === \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
                throw new LocalizedException(__('Payment is under review. Please check transaction status before capturing.'));
            }
            throw new LocalizedException(__('No authorization transaction found for capture.'));
        }

        $capturePayload = [
            'requestType' => 'PostAuthTransaction',
            'transactionAmount' => [
                'total' => number_format((float)$amount, 2, '.', ''),
                'currency' => $order->getOrderCurrencyCode()
            ]
        ];

        $this->helper->addLog($capturePayload, true);
        $response = $this->helper->callCurl("payments/{$authTxnId}", $capturePayload, 'POST', '', $order->getStoreId());
        $this->helper->addLog($response, true);

        if ($response['status'] === 'success' && !empty($response['response']->ipgTransactionId)) {
            $apiResponse = $response['response'];

            if (isset($apiResponse->transactionStatus) && $apiResponse->transactionStatus === 'APPROVED') {
                $payment->setTransactionId($apiResponse->ipgTransactionId);
                $payment->setIsTransactionClosed(true);
                $payment->setIsTransactionPending(false);

                // Clear any payment review status
                if ($order->getState() === \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                }

                $this->helper->updatePaymentRecord($order, $apiResponse);
            } else {
                $message = $apiResponse->errorMessage ?? 'Capture transaction not approved';
                throw new LocalizedException(__($message));
            }
        } else {
            $errorMsg = 'Capture failed - unable to complete authorization';
            if (isset($response['response']->error->message)) {
                $errorMsg = $response['response']->error->message;
            } elseif (isset($response['response']->errorMessage)) {
                $errorMsg = $response['response']->errorMessage;
            }
            throw new LocalizedException(__($errorMsg));
        }

        return $this;
    }

    /**
     * Accept payment that is under review
     *
     * @param InfoInterface $payment
     * @return false
     * @throws LocalizedException
     */
    public function acceptPayment(InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        // Clear payment review status and set to processing
        if ($order->getState() === \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
            $paymentAction = $this->getConfigPaymentAction();

            if ($paymentAction === self::PAYMENT_ACTION_AUTHORIZE) {
                $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                $order->setStatus('pending_payment');
            } else {
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            }

            $payment->setIsTransactionPending(false);
            $this->orderRepository->save($order);
        }

        return false;
    }

    /**
     * Deny payment that is under review
     *
     * @param InfoInterface $payment
     * @return false
     * @throws LocalizedException
     */
    public function denyPayment(InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        // Cancel the order if it's under review
        if ($order->getState() === \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
            if ($order->canCancel()) {
                $order->cancel();
                $this->orderRepository->save($order);
            }
        }

        return false;
    }
}
