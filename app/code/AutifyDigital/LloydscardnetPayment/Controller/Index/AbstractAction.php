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

namespace AutifyDigital\LloydscardnetPayment\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Framework\Api\Filter;
use Magento\Store\Model\StoreManagerInterface;
use AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Class AbstractAction
 */
abstract class AbstractAction extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Lcnetpaymentjs
     */
    protected $lcnetPaymentjs;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     * */
    protected $helper;

    /**
     * @var string
     */
    protected $formKey;

    /**
     * @var string
     */
    protected $_baseUrl; // phpcs:ignore

    /**
     * @var string
     */
    protected $_cancel_url; // phpcs:ignore
    /**
     * @var string
     */
    protected $_return_url; // phpcs:ignore

    /**
     * @var string
     */
    protected $mediaUrl;

     /**
      * @var Config
      */
    protected $config;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

     /**
      * @var OrderFactory
      */
    protected $orderFactory;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory
     */
    protected $lcPaymentsFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession; // phpcs:ignore

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PaymentTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * @var PaymentsCollectionFactory
     */
    protected $paymentsCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * AbstractLloydsAction constructor
     *
     * @param Context $context
     * @param HelperData $helperData
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param OrderFactory $orderFactory
     * @param \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param SerializerInterface $serializer
     * @param PaymentTokenFactory $paymentTokenFactory
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param StoreManagerInterface $storeManager
     * @param Lcnetpaymentjs $lcnetPaymentjs
     * @param EncryptorInterface $encryptor
     * @param DateTime $dateTime
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ResultFactory $resultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        Config $config,
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory,
        LayoutFactory $resultLayoutFactory,
        OrderFactory $orderFactory,
        \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory,
        CartRepositoryInterface $quoteRepository,
        SerializerInterface $serializer,
        PaymentTokenFactory $paymentTokenFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        StoreManagerInterface $storeManager,
        Lcnetpaymentjs $lcnetPaymentjs,
        EncryptorInterface $encryptor,
        DateTime $dateTime,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResultFactory $resultFactory,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        SessionManagerInterface $coreSession
    ) {
        parent::__construct($context);
        $this->helper = $helperData;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->orderFactory = $orderFactory;
        $this->lcPaymentsFactory = $lcPaymentsFactory;
        $this->quoteRepository = $quoteRepository;
        $this->serializer = $serializer;
        $this->_baseUrl = $this->config->getStoreUrl();
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->storeManager = $storeManager;
        $this->lcnetPaymentjs = $lcnetPaymentjs;
        $this->encryptor = $encryptor;
        $this->dateTime = $dateTime;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resultFactory = $resultFactory;
        $this->orderRepository = $orderRepository;
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->_coreSession = $coreSession;
    }

    /**
     * Create Csrf Validation Exception
     *
     * @param RequestInterface $request
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Validate For Csrf
     *
     * @param RequestInterface $request
     * return Boolean
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Initialize Core Parameters
     *
     * @return array
     */
    protected function initializeUrls()
    {
        $orderId = $this->getCurrentOrder()->getId();
        $cancelUrl = $this->_url->getUrl(
            'lcnetpayment/index/cancel',
            ['SID' => $this->_coreSession->getSessionId(),
            'order_id' => $orderId]
        );
        $returnUrl = $this->_url->getUrl(
            'lcnetpayment/index/redirectResponse',
            ['SID' => $this->_coreSession->getSessionId(),
            'order_id' => $orderId]
        );
        $transactionNotificationURL = $this->_url->getUrl(
            'lcnetpayment/index/transactionNotificationUrl',
            ['SID' => $this->_coreSession->getSessionId(),
            'order_id' => $orderId]
        );
        $methodNotificationURL = $this->_url->getUrl(
            'lloyds/paymentjs/methodNotificationUrl',
            ['SID' => $this->_coreSession->getSessionId(),
            'order_id' => $orderId]
        );

        $termUrl = $this->_url->getUrl(
            'lloyds/paymentjs/termResponse',
            ['SID' => $this->_coreSession->getSessionId(), 'order_id' => $orderId]
        );

        $config['cancel_url'] = $cancelUrl;
        $config['return_url'] = $returnUrl;
        $config['transaction_notification_url'] = $transactionNotificationURL;
        $config['method_notification_url'] = $methodNotificationURL;
        $config['term_url'] = $termUrl;

        return $config;
    }

    /**
     * Get Current Order
     *
     * @return Order
     */
    public function getCurrentOrder()
    {
        return $this->checkoutSession->getLastRealOrder();
    }

    /**
     * Initialize ReDirect Payment Parameters
     *
     * @param string $mode
     * @param int|string|null $storeId
     * @return array
     */
    protected function initializeReDirectPaymentParameters($mode, $storeId = null)
    {
        $config = $this->config->getBasicConfigurations($mode, $storeId);
        $allUrls = $this->initializeUrls();

        $allParams = array_merge($config, $allUrls);
        return $allParams;
    }
}
