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

namespace AutifyDigital\LloydscardnetPayment\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Serialize\SerializerInterface;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as PaymentsFactory;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;

class Redirect implements HttpGetActionInterface
{
    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

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
     * @var CustomerSession
     */
    protected $customerSession;

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
     * @var OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;

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
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param ResultFactory $resultFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     * @param Data $helper
     * @param Json $jsonSerializer
     * @param RequestInterface $request
     * @param CustomerSession $customerSession
     * @param EncryptorInterface $encryptor
     * @param DateTime $dateTime
     * @param SerializerInterface $serializer
     * @param PaymentsFactory $paymentsFactory
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     */
    public function __construct(
        OrderFactory $orderFactory,
        Session $checkoutSession,
        ResultFactory $resultFactory,
        ScopeConfigInterface $scopeConfig,
        Config $config,
        Data $helper,
        Json $jsonSerializer,
        RequestInterface $request,
        CustomerSession $customerSession,
        EncryptorInterface $encryptor,
        DateTime $dateTime,
        SerializerInterface $serializer,
        PaymentsFactory $paymentsFactory,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository
    ) {
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $resultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->encryptor = $encryptor;
        $this->dateTime = $dateTime;
        $this->serializer = $serializer;
        $this->paymentsFactory = $paymentsFactory;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentsRepository = $paymentsRepository;
    }

    /**
     * Redirect to payment gateway
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var ResultJson $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $orderId = $this->checkoutSession->getLastRealOrderId();
        $this->helper->addLog('Fiserv Checkout started for order: ' . $orderId);

        if (!$orderId) {
            return $resultJson->setData([
                'error' => true,
                'message' => 'No order found'
            ]);
        }

        try {
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            $payment = $order->getPayment();

            $tokenIdParam = $this->request->getParam('token_id');
            $tokenId = $tokenIdParam ? $this->helper->getTokenValue($this->encryptor->decrypt($tokenIdParam)) : null;
            $saveCard = (bool)$this->request->getParam('save_card', false);

            if ($payment) {
                $additionalInfo = $payment->getAdditionalInformation() ?: [];
                $additionalInfo['save_card'] = $saveCard;
                if ($tokenId) {
                    $additionalInfo['token_id'] = $tokenId;
                }
                $payment->setAdditionalInformation($additionalInfo);
                $this->orderPaymentRepository->save($payment);
            }

            $paymentMode = $this->config->getConfig('payment/lbopcheckoutsolution/lloyds_mode');
            $basicConfig = $this->config->getBasicConfigurations($paymentMode);

            $basketItems = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $basketItems[] = [
                    'itemIdentifier' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => (float)$item->getPrice(),
                    'quantity' => (int) $item->getQtyOrdered(),
                    'shippingCost' => 0,
                    'valueAddedTax' => 0,
                    'miscellaneousFee' => 0,
                    'total' => (float) $item->getRowTotalInclTax()
                ];
            }

            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();
            $storeId = $basicConfig['store_id'];
            if (!$this->helper->startsWith((string)$storeId, '22')) {
                $this->helper->addLog('Invalid store ID : ' . $storeId);
                $this->helper->restoreQuote();
                return $resultJson->setData([
                    'error' => true,
                    'message' => 'Invalid storeId. Please contact support.'
                ]);
            }

            // Fallback to billing address for virtual orders
            $shippingAddress = $order->getShippingAddress();
            if (!$shippingAddress || $shippingAddress->getFirstname() == null) {
                $shippingAddress = $order->getBillingAddress();
            }
            
            $paymentData = [
                'storeId' => $basicConfig['store_id'],
                'transactionOrigin' => 'ECOM',
                'transactionType' => 'SALE',
                'transactionAmount' => [
                    'currency' => $order->getOrderCurrencyCode(),
                    'total' => (float)$order->getGrandTotal(),
                ],
                'order' => [
                    'orderDetails' => [
                        'customerId' => (string)$order->getCustomerId(),
                        'invoiceNumber' => (string)$order->getIncrementId(),
                        'purchaseOrderNumber' => (string)$order->getIncrementId()
                    ],
                    'basket' => [
                        'lineItems' => $basketItems
                    ],
                    'billing' => [
                        'person' => [
                            'firstName' => $billingAddress->getFirstname(),
                            'lastName' => $billingAddress->getLastname(),
                            'name' => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
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
                            'name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
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
                        'successUrl' =>  $this->helper->getUrl('lloyds/checkout/success'),
                        'failureUrl' => $this->helper->getUrl('lloyds/checkout/failure'),
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

            $paymentData['checkoutSettings']['webHooksUrl'] = $this->helper
            ->getUrl('lloyds/checkout/webhook') . '?order_id=' .
            base64_encode($order->getId()) . '&_secure=true';

            if ($saveCard && $order->getCustomerId()) {
                $paymentData['paymentMethodDetails']['cards']['createToken'] = [
                    'reusable' => true,
                    'toBeUsedFor' => 'UNSCHEDULED'
                ];
            }

            if ($tokenId) {
                $paymentData['paymentMethodDetails']['cards']['tokenBasedTransaction'] = [
                    'value' => $tokenId,
                    'transactionSequence' => 'SUBSEQUENT'
                ];
            }

            $this->helper->addLog('API Request: ');
            $this->helper->addLog($paymentData, true);
            $response = $this->helper->callFiservCurl(Data::FISERV_API_URL, $paymentData, 'POST');
            $this->helper->addLog('API Response: ');
            $this->helper->addLog($response, true);

            if (isset($response['status']) && $response['status'] === 'success') {
                $checkoutId = $response['response']['checkout']['checkoutId'];
                $order->setData('fiserv_checkout_id', $checkoutId);
                $this->orderRepository->save($order);

                $this->saveInitialPaymentRecord($order, $checkoutId, $saveCard);

                if (isset($response['response']['checkout']['threeDSecure']) &&
                    isset($response['response']['checkout']['ipgTransactionId'])
                ) {
                    return $resultJson->setData([
                        '3dsframe' => true,
                        'ipg_transaction_id' => $this->encryptor
                        ->encrypt($response['response']['checkout']['ipgTransactionId']),
                        'data' => $this->encryptor->encrypt($response['response']['checkout']['threeDSecure'])
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

                $this->helper->addLog('Payment error: ' . $errorMessage);

                return $resultJson->setData([
                    'error' => true,
                    'message' => $errorMessage
                ]);
            }
        } catch (\Exception $e) {
            $this->helper->addLog('Exception: ' . $e->getMessage());
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
            $amount = (float)$order->getGrandTotal();
            if ($amount <= 0) {
                $amount = number_format((float)$order->getBaseGrandTotal(), 2, '.', '');
            }
            $paymentModel->setData('amount', $amount);
            $paymentModel->setData('order_id', $order->getId());
            $paymentModel->setData('order_increment_id', $order->getIncrementId());
            $paymentModel->setData('cardnet_order_id', $order->getIncrementId());
            $paymentModel->setData('checkout_id', $checkoutId);
            $paymentModel->setData('save_card', $saveCard);
            $paymentModel->setData('status', 1);
            $paymentModel->setData('created_at', $this->dateTime->gmtDate());

            $this->paymentsRepository->save($paymentModel);
            
            $this->helper->addLog(
                'Initial payment record saved with pending status for order: ' .
                $order->getIncrementId()
            );
        } catch (\Exception $e) {
            $this->helper->addLog('Error saving initial payment record: ' . $e->getMessage());
        }
    }
}
