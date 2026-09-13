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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Payments;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Backend\Model\Session\Quote as AdminCheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Backend\Model\Auth;

/**
 * Class AbstractAdminAction
 */
abstract class AbstractAction extends Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var Auth
     */
    protected $_auth;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory
     */
    protected $lcPaymentsFactory;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var AdminCheckoutSession
     */
    protected $adminCheckoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * @var string
     */
    protected $_baseUrl;

    /**
     * AbstractAdminAction constructor.
     *
     * @param Context $context
     * @param HelperData $helperData
     * @param Config $config
     * @param JsonFactory $resultJsonFactory
     * @param OrderFactory $orderFactory
     * @param \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory
     * @param SerializerInterface $serializer
     * @param AdminCheckoutSession $adminCheckoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderManagementInterface $orderManagement
     * @param Auth $auth
     * @param PaymentsRepositoryInterface $paymentsRepository
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        Config $config,
        JsonFactory $resultJsonFactory,
        OrderFactory $orderFactory,
        \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory,
        SerializerInterface $serializer,
        AdminCheckoutSession $adminCheckoutSession,
        OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement,
        Auth $auth,
        PaymentsRepositoryInterface $paymentsRepository
    ) {
        parent::__construct($context);
        $this->helper = $helperData;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
        $this->lcPaymentsFactory = $lcPaymentsFactory;
        $this->serializer = $serializer;
        $this->adminCheckoutSession = $adminCheckoutSession;
        $this->orderRepository = $orderRepository;
        $this->orderManagement = $orderManagement;
        $this->_auth = $auth;
        $this->paymentsRepository = $paymentsRepository;
        $this->_baseUrl = $this->config->getStoreUrl();
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
     * Initialize URLs
     *
     * @param int $orderId
     * @return array
     */
    protected function initializeAdminUrls($orderId)
    {
        $adminSessionId = $this->adminCheckoutSession->getSessionId();
        $baseUrl = $this->_baseUrl;
        $cancelUrl = $baseUrl . 'lloyds/index/motoResponseUrl';
        $returnUrl = $baseUrl . 'lloyds/index/motoResponseUrl';
        $transactionNotificationURL = $baseUrl . 'lloyds/index/transactionNotificationUrlMOTO';

        $config['cancel_url'] = $cancelUrl;
        $config['return_url'] = $returnUrl;
        $config['transaction_notification_url'] = $transactionNotificationURL;

        return $config;
    }

    /**
     * Initialize Redirect Payment Parameters
     *
     * @param string $mode
     * @param mixed $orderId
     * @param int|string|null $storeId
     * @return array
     */
    protected function initializeAdminRedirectPaymentParameters($mode, $orderId, $storeId = null)
    {
        $config = $this->config->getBasicConfigurations($mode, $storeId);
        $allUrls = $this->initializeAdminUrls($orderId);

        $allParams = array_merge($config, $allUrls);
        return $allParams;
    }

    /**
     * Get admin order by ID
     *
     * @param int $orderId
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    protected function getAdminOrder($orderId)
    {
        return $this->orderRepository->get($orderId);
    }
}
