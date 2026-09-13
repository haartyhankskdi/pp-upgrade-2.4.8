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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Checkout;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Serialize\SerializerInterface;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as PaymentsFactory;
use Magento\Backend\Model\Session\Quote as AdminQuoteSession;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;

class Redirect extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Sales::create';

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var AdminQuoteSession
     */
    protected $adminQuoteSession;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var PaymentsFactory
     */
    protected $paymentsFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param AdminQuoteSession $adminQuoteSession
     * @param ResultFactory $resultFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     * @param Data $helper
     * @param Json $jsonSerializer
     * @param RequestInterface $request
     * @param EncryptorInterface $encryptor
     * @param DateTime $dateTime
     * @param SerializerInterface $serializer
     * @param PaymentsFactory $paymentsFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        AdminQuoteSession $adminQuoteSession,
        ResultFactory $resultFactory,
        ScopeConfigInterface $scopeConfig,
        Config $config,
        Data $helper,
        Json $jsonSerializer,
        RequestInterface $request,
        EncryptorInterface $encryptor,
        DateTime $dateTime,
        SerializerInterface $serializer,
        PaymentsFactory $paymentsFactory,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->adminQuoteSession = $adminQuoteSession;
        $this->resultFactory = $resultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
        $this->request = $request;
        $this->encryptor = $encryptor;
        $this->dateTime = $dateTime;
        $this->serializer = $serializer;
        $this->paymentsFactory = $paymentsFactory;
        $this->orderRepository = $orderRepository;
        $this->paymentsRepository = $paymentsRepository;
    }

    /**
     * Redirect to payment gateway for admin order
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $orderId = $this->request->getParam('order_id');
        if (!$orderId) {
            $orderId = $this->adminQuoteSession->getOrderId();
        }

        $this->helper->addLog('Fiserv Admin Checkout started for order ID: ' . $orderId);

        if (!$orderId) {
            return $resultJson->setData([
                'error' => true,
                'message' => 'No order found'
            ]);
        }

        try {
            try {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderRepository->get($orderId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return $resultJson->setData([
                    'error' => true,
                    'message' => 'Order not found'
                ]);
            }

            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();

            if ($payment) {
                $existingInfo = $payment->getAdditionalInformation() ?: [];
                $payment->setAdditionalInformation(array_merge($existingInfo, [
                    'save_card' => 0,
                    'admin_order' => true
                ]));
            }

            $storeId = $order->getStoreId();
            $paymentMode = $this->config->getConfig('payment/lbopcheckoutsolution/lloyds_mode', $storeId);
            $basicConfig = $this->config->getBasicConfigurations($paymentMode, $storeId);

            $basketItems = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $basketItems[] = [
                    'itemIdentifier' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => (float)$item->getPrice(),
                    'quantity' => (int)$item->getQtyOrdered(),
                    'shippingCost' => 0,
                    'valueAddedTax' => (float)$item->getTaxAmount(),
                    'miscellaneousFee' => 0,
                    'total' => (float)$item->getPrice() * (int)$item->getQtyOrdered() + (float)$item->getTaxAmount()
                ];
            }
            
            $shippingAmount = (float)$order->getShippingAmount();
            $shippingTax = (float)$order->getShippingTaxAmount();
            
            if ($shippingAmount > 0 || $shippingTax > 0) {
                $basketItems[] = [
                    'itemIdentifier' => 'SHIPPING',
                    'name' => $order->getShippingDescription() ?: 'Flat Rate - Fixed',
                    'price' => $shippingAmount,
                    'quantity' => 1,
                    'shippingCost' => 0,
                    'valueAddedTax' => $shippingTax,
                    'miscellaneousFee' => 0,
                    'total' => $shippingAmount + $shippingTax
                ];
            }
            
            $enablePONumber = $this->config->getConfig('payment/basic/active_po_number', $storeId);
            $configPONumber = $this->config->getConfig('payment/basic/dynamic_data_po_number', $storeId);

            $ponumber = '';
            if ($enablePONumber == 1) {
                $configponumberarray = explode("|", $configPONumber);
                $keys = array_keys($configponumberarray);
                $lastKey = end($keys);
                
                foreach ($configponumberarray as $key => $configponumberval) {
                    if ($key == $lastKey) {
                        $ponumber .= $order->getData($configponumberval);
                    } else {
                        if ($order->getData($configponumberval)) {
                            $ponumber .= $order->getData($configponumberval) . "_";
                        }
                    }
                }
                if ($ponumber) {
                    $ponumber = substr($ponumber, 0, 49);
                }
            }

            /** @var \Magento\Sales\Model\Order\Address|null $billingAddress */
            $billingAddress = $order->getBillingAddress();
            /** @var \Magento\Sales\Model\Order\Address|null $shippingAddress */
            $shippingAddress = $order->getShippingAddress() ?: $billingAddress;

            $billingName = trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
            $shippingName = trim($shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname());

            $paymentData = [
                'storeId' => $basicConfig['store_id'],
                'transactionOrigin' => 'PHONE',
                'transactionType' => 'SALE',
                'transactionAmount' => [
                    'currency' => $order->getOrderCurrencyCode(),
                    'total' => (float)$order->getGrandTotal(),
                ],
                'order' => [
                    'orderId' => (string)$this->helper->getOrderIdWithSuffix($order->getIncrementId()),
                    'orderDetails' => [
                        'customerId' => (string)$order->getCustomerId(),
                        'invoiceNumber' => (string)$order->getIncrementId(),
                        'purchaseOrderNumber' => (string)$ponumber
                    ],
                    'basket' => [
                        'lineItems' => $basketItems
                    ],
                    'billing' => [
                        'person' => [
                            'firstName' => $billingAddress->getFirstname(),
                            'lastName' => $billingAddress->getLastname(),
                            'name' => $billingName,
                        ],
                        'contact' => [
                            'phone' => $billingAddress->getTelephone(),
                            'email' => $order->getCustomerEmail(),
                        ],
                    ],
                    'shipping' => [
                        'person' => [
                            'firstName' => $shippingAddress->getFirstname(),
                            'lastName' => $shippingAddress->getLastname(),
                            'name' => $shippingName,
                        ],
                        'contact' => [
                            'phone' => $shippingAddress->getTelephone(),
                            'email' => $order->getCustomerEmail(),
                        ],
                        'address' => [
                            'address1' => $shippingAddress->getStreet()[0] ?? '',
                            'address2' => $shippingAddress->getStreet()[1] ?? '',
                            'city' => $shippingAddress->getCity(),
                            'country' => $shippingAddress->getCountryId(),
                            'postalCode' => $shippingAddress->getPostcode(),
                            'region' => $shippingAddress->getRegion(),
                            'company' => $shippingAddress->getCompany()
                        ]
                    ]
                ],
                'checkoutSettings' => [
                    'locale' => $this->helper->getLocale(),
                    'redirectBackUrls' => [
                        'successUrl' => $this->getUrl('autify_lloydscardnetpayment/checkout/success', [
                            'order_id' => $order->getId()
                        ]),
                        'failureUrl' => $this->getUrl('autify_lloydscardnetpayment/checkout/failure', [
                            'order_id' => $order->getId()
                        ]),
                    ],
                ],
                'paymentMethodDetails' => [
                    'cards' => [
                        'authenticationPreferences' => [
                            'challengeIndicator' => '01',
                            'skipTra' => false
                        ]
                    ]
                ]
            ];

            $paymentData['checkoutSettings']['webHooksUrl'] =
            $this->getUrl('autify_lloydscardnetpayment/checkout/webhook');

            $this->helper->addLog('Admin API Request: ');
            $this->helper->addLog($paymentData, true);
            $response = $this->helper->callFiservCurl(Data::FISERV_API_URL, $paymentData, 'POST', $storeId);
            $this->helper->addLog('Admin API Response: ');
            $this->helper->addLog($response, true);

            if (isset($response['status']) && $response['status'] === 'success') {
                $checkoutId = $response['response']['checkout']['checkoutId'];
                $order->setData('fiserv_checkout_id', $checkoutId);
                $this->orderRepository->save($order);

                // Save initial payment record with pending status
                $this->saveInitialPaymentRecord($order, $checkoutId, 0);

                // For 3DS processing if needed
                if (isset($response['response']['checkout']['threeDSecure']) &&
                    isset($response['response']['checkout']['ipgTransactionId'])
                ) {
                    return $resultJson->setData([
                        '3dsframe' => true,
                        'ipg_transaction_id' => $response['response']['checkout']['ipgTransactionId'],
                        'data' => $response['response']['checkout']['threeDSecure']
                    ]);
                }

                return $resultJson->setData([
                    'url' => $response['response']['checkout']['redirectionUrl']
                ]);
            } else {
                $errorMessage = 'Payment gateway error';
                if (isset($response['response']['error']['message'])) {
                    $errorMessage = $response['response']['error']['message'];
                }

                $this->helper->addLog('Admin Payment error: ' . $errorMessage);

                return $resultJson->setData([
                    'error' => true,
                    'message' => $errorMessage
                ]);
            }
        } catch (\Exception $e) {
            $this->helper->addLog('Admin Exception: ' . $e->getMessage());
            return $resultJson->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save initial payment record with pending status
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $checkoutId
     * @param bool $saveCard
     * @return void
     */
    private function saveInitialPaymentRecord($order, $checkoutId, $saveCard)
    {
        try {
            $paymentModel = $this->paymentsFactory->create();
            $total = number_format((float)$order->getGrandTotal(), 2, '.', '');
            if ($total <= 0) {
                $total = number_format((float)$order->getBaseGrandTotal(), 2, '.', '');
            }
            $paymentModel->setData('amount', $total);
            $paymentModel->setData('order_id', $order->getId());
            $paymentModel->setData('order_increment_id', $order->getIncrementId());
            $paymentModel->setData('cardnet_order_id', $order->getIncrementId());
            $paymentModel->setData('checkout_id', $checkoutId);
            $paymentModel->setData('save_card', 0);
            $paymentModel->setData('status', 1);
            $paymentModel->setData('created_at', $this->dateTime->gmtDate());

            $this->paymentsRepository->save($paymentModel);

            $this->helper->addLog(
                'Initial MOTO Checkout Solution - payment record saved with pending status for order: ' .
                $order->getIncrementId()
            );
        } catch (\Exception $e) {
            $this->helper->addLog('Error saving initial MOTO Checkout Solution payment record: ' . $e->getMessage());
        }
    }
}
