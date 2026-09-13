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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction as DbTransaction;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as PaymentsFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Success implements HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

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
     * @var BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var DbTransaction
     */
    protected $dbTransaction;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var PaymentsFactory
     */
    protected $paymentsFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

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
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Constructor
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param ResultFactory $resultFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     * @param Data $helper
     * @param Json $jsonSerializer
     * @param BuilderInterface $transactionBuilder
     * @param InvoiceService $invoiceService
     * @param DbTransaction $dbTransaction
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param PaymentsFactory $paymentsFactory
     * @param CustomerSession $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        ResultFactory $resultFactory,
        ScopeConfigInterface $scopeConfig,
        Config $config,
        Data $helper,
        Json $jsonSerializer,
        BuilderInterface $transactionBuilder,
        InvoiceService $invoiceService,
        DbTransaction $dbTransaction,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        PaymentsFactory $paymentsFactory,
        CustomerSession $customerSession,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        DateTime $dateTime
    ) {
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $resultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
        $this->transactionBuilder = $transactionBuilder;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->paymentsFactory = $paymentsFactory;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dateTime = $dateTime;
        $this->_request = $context->getRequest();
    }

    /**
     * Process successful payment response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helper->addlog('----Success Callback Start----');
        try {
            $data = $this->getRequest()->getParams();
            $this->helper->addlog('Success callback received with params: ');
            $this->helper->addLog($data, true);

            $orderId = $this->checkoutSession->getLastOrderId();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $orderId ? $this->orderRepository->get((int)$orderId) : $this->orderFactory->create();

            if (!$order->getId()) {
                $this->helper->addlog('Order not found in session, trying to find by other means');

                if (isset($data['checkoutId'])) {
                    $checkoutId = $data['checkoutId'];
                    $order = $this->loadOrderByCheckoutId($checkoutId);
                }

                if (!$order->getId() && isset($data['orderId'])) {
                    $orderId = $this->helper->stripOrderIdSuffix($data['orderId']);
                    $order = $this->orderFactory->create()->loadByIncrementId($orderId);
                    $this->helper->addlog('Trying to load order by orderId from params: ' . $orderId);
                }

                if (!$order->getId()) {
                    throw new LocalizedException(__('Order not found. Session order ID: ' . $orderId));
                }
            }

            $this->helper->addlog('Order found: ' . $order->getIncrementId());

            $checkoutId = $order->getData('fiserv_checkout_id');
            if (!$checkoutId && isset($data['checkoutId'])) {
                $checkoutId = $data['checkoutId'];
                $order->setData('fiserv_checkout_id', $checkoutId);
                $this->orderRepository->save($order);
            }

            if (!$checkoutId) {
                throw new LocalizedException(__('No checkout ID found for order: ' . $order->getIncrementId()));
            }

            $paymentMode = $this->config->getConfig('payment/lbopcheckoutsolution/lloyds_mode');

            $transactionDetails = $this->getTransactionDetails($checkoutId, $paymentMode);

            if (empty($transactionDetails)) {
                throw new LocalizedException(__('Failed to retrieve transaction details for checkout ID: ' .
                $checkoutId));
            }

            if (isset($transactionDetails['transactionStatus']) &&
                $transactionDetails['transactionStatus'] === 'APPROVED'
            ) {
                $this->processSuccessfulPayment($order, $transactionDetails);

                $this->updatePaymentRecordToProcessing($order, $transactionDetails);

                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout/onepage/success');
            } else {
                $errorMessage = isset($transactionDetails['errorMessage']) ? $transactionDetails['errorMessage'] :
                                (isset($transactionDetails['ipgTransactionDetails']['transactionResult']) ?
                                $transactionDetails['ipgTransactionDetails']['transactionResult'] :
                                'Payment was not approved');

                $this->helper->addlog('Payment not approved: ' . $errorMessage);

                $this->updatePaymentRecordToFailed($order, $transactionDetails, $errorMessage);

                $this->cancelOrder($order, $errorMessage);
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout/cart');
            }
        } catch (\Exception $e) {
            $this->helper->addlog('Error in success controller: ' . $e->getMessage());

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout/cart');
        }
    }

    /**
     * Load order by checkout ID
     *
     * @param string $checkoutId
     * @return \Magento\Sales\Model\Order
     */
    private function loadOrderByCheckoutId($checkoutId)
    {
        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('fiserv_checkout_id', $checkoutId)
            ->setPageSize(1);

        if ($orderCollection->getSize()) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $orderCollection->getFirstItem();
            return $order;
        }

        return $this->orderFactory->create();
    }

    /**
     * Get transaction details from payment gateway
     *
     * @param string $checkoutId
     * @param string $paymentMode
     * @return array
     */
    private function getTransactionDetails($checkoutId, $paymentMode)
    {
        try {
            $response = $this->helper->getCheckoutDetailsById($checkoutId);
            $this->helper->addlog('Response from payment gateway:');
            $this->helper->addlog($response, true);

            if (isset($response['status']) && $response['status'] === 'success' && isset($response['response'])) {
                if (is_object($response['response'])) {
                    return $this->objectToArray($response['response']);
                }
                return (array)$response['response'];
            }
        } catch (\Exception $e) {
            $this->helper->addlog('Error fetching transaction details: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Convert stdClass objects to arrays recursively
     *
     * @param mixed $obj
     * @return array
     */
    private function objectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array)$obj;
        }

        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $obj[$key] = $this->objectToArray($value);
            }
        }

        return $obj;
    }

    /**
     * Process successful payment
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $transactionDetails
     * @return void
     */
    private function processSuccessfulPayment($order, $transactionDetails)
    {
        try {
            $payment = $order->getPayment();

            $transactionId = isset($transactionDetails['ipgTransactionDetails']['ipgTransactionId'])
                ? $transactionDetails['ipgTransactionDetails']['ipgTransactionId']
                : '';

            $authCode = isset($transactionDetails['ipgTransactionDetails']['approvalCode'])
                ? $transactionDetails['ipgTransactionDetails']['approvalCode']
                : '';

            $payment->setLastTransId($transactionId);
            $additionalInfo = $payment->getAdditionalInformation() ?: [];
            $additionalInfo['transaction_id'] = $transactionId;
            $additionalInfo['auth_code'] = $authCode;
            $payment->setAdditionalInformation($additionalInfo);

            if (isset($transactionDetails['ipgTransactionDetails']['paymentToken']) && $order->getCustomerId()) {
                $saveCard = $additionalInfo['save_card'] ?? false;
                if ($saveCard) {
                    $this->handleTokenSaving($transactionDetails, $order);
                }
            }

            if (isset($transactionDetails['paymentMethodUsed']['cards']['cardNumber'])) {
                $cardNumber = $transactionDetails['paymentMethodUsed']['cards']['cardNumber'];
                $payment->setCcLast4(substr($cardNumber, -4));
                $payment->setCcType($this->helper->detectCardType($cardNumber));
                $additionalInfo['cc_number_masked'] = $cardNumber;
            }

            if (!empty($transactionDetails)) {
                $additionalInfo['transaction_details'] = $this->jsonSerializer->serialize($transactionDetails);

                if (isset($transactionDetails['approvedAmount']['total']) &&
                    isset($transactionDetails['approvedAmount']['currency'])
                ) {
                    $additionalInfo['approved_amount'] = $transactionDetails['approvedAmount']['total'];
                    $additionalInfo['currency'] = $transactionDetails['approvedAmount']['currency'];
                }
            }

            $payment->setAdditionalInformation($additionalInfo);

            $this->transactionBuilder
                ->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionId)
                ->setAdditionalInformation([
                    Transaction::RAW_DETAILS => $transactionDetails
                ])
                ->setFailSafe(true)
                ->build(Transaction::TYPE_CAPTURE);

            $order->setState(Order::STATE_PROCESSING)
                ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));

            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setData('requested_capture_case', \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();

                $this->dbTransaction
                    ->addObject($invoice)
                    ->addObject($order)
                    ->save();

                $this->invoiceSender->send($invoice);

                $this->helper->addlog('Invoice created for order: ' . $order->getIncrementId());
            }

            if (!$order->getEmailSent()) {
                $this->orderSender->send($order);
                $order->setEmailSent(1);
            }

            $this->orderRepository->save($order);

            $this->helper->addlog('Order ' . $order->getIncrementId() . ' payment processed successfully');
        } catch (\Exception $e) {
            $this->helper->addlog('Error processing payment: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle token saving for card
     *
     * @param array $transactionDetails
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function handleTokenSaving($transactionDetails, $order)
    {
        try {
            $tokenValue = $transactionDetails['ipgTransactionDetails']['paymentToken']['value'] ?? '';
            $schemeTransactionId = $transactionDetails['ipgTransactionDetails']['schemeTransactionId'] ?? null;
            $maskedCardNumber = $transactionDetails['paymentMethodUsed']['cards']['cardNumber'] ?? '';
            $brand = $transactionDetails['paymentMethodUsed']['cards']['brand'] ?? '';
            $last4 = substr($maskedCardNumber, -4);
            $expMonth = $transactionDetails['paymentMethodUsed']['cards']['expiryDate']['month'] ?? '';
            $expYear = $transactionDetails['paymentMethodUsed']['cards']['expiryDate']['year'] ?? '';
            $responseMessage = $transactionDetails['ipgTransactionDetails']['processor']['responseMessage'] ?? '';

            $paymentTokenData = [
                'customer_id' => $order->getCustomerId(),
                'payment_token' => $tokenValue,
                'masked' => $maskedCardNumber,
                'brand' => $brand,
                'last4' => $last4,
                'exp_month' => $expMonth,
                'exp_year' => $expYear,
                'is_fiserv' => 1
            ];

            if ($schemeTransactionId !== null) {
                $paymentTokenData['scheme_transaction_id'] = $schemeTransactionId;
            }

            $this->helper->savePaymentToken($paymentTokenData);
            $this->helper->addlog('Payment token saved for customer: ' . $order->getCustomerId());
        } catch (\Exception $e) {
            $this->helper->addlog('Error saving payment token: ' . $e->getMessage());
        }
    }

    /**
     * Update payment record to processing status
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $transactionDetails
     * @return void
     */
    private function updatePaymentRecordToProcessing($order, $transactionDetails)
    {
        try {
            $collection = $this->paymentsCollectionFactory->create()
                ->addFieldToFilter('order_id', (string)$order->getId())
                ->addFieldToFilter('status', '1')
                ->setPageSize(1);

            if ($collection->getSize() > 0) {
                /** @var \AutifyDigital\LloydscardnetPayment\Model\Payments $paymentRecord */
                $paymentRecord = $collection->getFirstItem();
                
                $response = $transactionDetails['ipgTransactionDetails'] ?? [];
                $approvalCode = $response['approvalCode'] ?? '';
                $transactionStatus = $response['transactionStatus'] ?? '';
                $ipgTransactionId = $response['ipgTransactionId'] ?? '';
                $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $ipgTransactionId;
                
                $response3dsCodeMessage = $response['processor']['responseMessage'] ?? '';

                $brand = '';
                $last4 = '';
                
                if (isset($transactionDetails['paymentMethodUsed']['cards'])) {
                    $cardData = $transactionDetails['paymentMethodUsed']['cards'];
                    $brand = $cardData['brand'] ?? '';
                    $cardNumber = $cardData['cardNumber'] ?? '';
                    $last4 = substr($cardNumber, -4);
                } elseif (isset($transactionDetails['ipgTransactionDetails']['paymentMethodDetails']['paymentCard'])) {
                    $cardData = $transactionDetails['ipgTransactionDetails']['paymentMethodDetails']['paymentCard'];
                    $brand = $cardData['brand'] ?? '';
                    $last4 = $cardData['last4'] ?? '';
                }

                $paymentRecord->setData('remote_reference', $ipgTransactionId);
                $paymentRecord->setData('remote_status_or_code', $transactionStatus);
                $paymentRecord->setData('approval_code', $approvalCode);
                $paymentRecord->setData('brand', $brand);
                $paymentRecord->setData('last4', $last4);
                $paymentRecord->setData('ipgTransactionId', $ipgTransactionId);
                $paymentRecord->setData('remote_message', $transactionMessage);
                $paymentRecord->setData('status', 2);
                $paymentRecord->setData('updated_at', $this->dateTime->date('Y-m-d H:i:s'));

                $this->paymentsRepository->save($paymentRecord);

                $this->helper->addLog(
                    'Payment record updated to processing status for order: ' . $order->getIncrementId()
                );
            } else {
                $this->helper->addLog('No pending payment record found for order: ' . $order->getIncrementId());
            }
        } catch (\Exception $e) {
            $this->helper->addLog('Error updating payment record to processing: ' . $e->getMessage());
        }
    }

    /**
     * Update payment record to failed status
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $transactionDetails
     * @param string $errorMessage
     * @return void
     */
    private function updatePaymentRecordToFailed($order, $transactionDetails, $errorMessage)
    {
        try {
            $collection = $this->paymentsCollectionFactory->create()
                ->addFieldToFilter('order_id', (string)$order->getId())
                ->addFieldToFilter('status', '1')
                ->setPageSize(1);

            if ($collection->getSize() > 0) {
                /** @var \AutifyDigital\LloydscardnetPayment\Model\Payments $paymentRecord */
                $paymentRecord = $collection->getFirstItem();
                
                $response = $transactionDetails['ipgTransactionDetails'] ?? [];
                $approvalCode = $response['approvalCode'] ?? '';
                $transactionStatus = $response['transactionStatus'] ?? '';
                $ipgTransactionId = $response['ipgTransactionId'] ?? '';
                $response3dsCodeMessage = $response['processor']['responseMessage'] ?? '';
                $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $ipgTransactionId;

                $paymentRecord->setData('remote_reference', $ipgTransactionId);
                $paymentRecord->setData('remote_status_or_code', $transactionStatus);
                $paymentRecord->setData('approval_code', $approvalCode);
                $paymentRecord->setData('remote_message', $transactionMessage);
                $paymentRecord->setData('status', 2);
                $paymentRecord->setData('response_3ds_code_message', $response3dsCodeMessage);
                $paymentRecord->setData('updated_at', $this->dateTime->date('Y-m-d H:i:s'));

                $this->paymentsRepository->save($paymentRecord);

                $this->helper->addLog('Payment record updated to failed status for order: ' . $order->getIncrementId());
            } else {
                $this->helper->addLog('No pending payment record found for order: ' . $order->getIncrementId());
            }
        } catch (\Exception $e) {
            $this->helper->addLog('Error updating payment record to failed: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $reason
     * @return void
     */
    private function cancelOrder($order, $reason)
    {
        if ($order->canCancel()) {
            $order->cancel();
            $order->addCommentToStatusHistory('Order canceled: ' . $reason);
            $this->orderRepository->save($order);
        }
    }

    /**
     * Retrieve request object
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    public function getRequest()
    {
        return $this->_request;
    }
}
