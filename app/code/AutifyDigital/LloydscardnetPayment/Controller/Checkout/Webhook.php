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

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction as DbTransaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as PaymentsFactory;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\App\Request\Http as HttpRequest;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    
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
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var DateTime
     */
    protected $dateTime;
    
    /**
     * @var Json
     */
    protected $jsonSerializer;
    
    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory
     */
    protected $paymentsFactory;

    /**
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var PaymentsCollectionFactory
     */
    protected $paymentsCollectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param OrderRepository $orderRepository
     * @param ResultFactory $resultFactory
     * @param BuilderInterface $transactionBuilder
     * @param InvoiceService $invoiceService
     * @param DbTransaction $dbTransaction
     * @param InvoiceSender $invoiceSender
     * @param Data $helper
     * @param EncryptorInterface $encryptor
     * @param DateTime $dateTime
     * @param Json $jsonSerializer
     * @param \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $paymentsFactory
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        ResultFactory $resultFactory,
        BuilderInterface $transactionBuilder,
        InvoiceService $invoiceService,
        DbTransaction $dbTransaction,
        InvoiceSender $invoiceSender,
        Data $helper,
        EncryptorInterface $encryptor,
        DateTime $dateTime,
        Json $jsonSerializer,
        \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $paymentsFactory,
        PaymentsRepositoryInterface $paymentsRepository,
        OrderCollectionFactory $orderCollectionFactory,
        PaymentsCollectionFactory $paymentsCollectionFactory
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->resultFactory = $resultFactory;
        $this->transactionBuilder = $transactionBuilder;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->invoiceSender = $invoiceSender;
        $this->helper = $helper;
        $this->encryptor = $encryptor;
        $this->dateTime = $dateTime;
        $this->jsonSerializer = $jsonSerializer;
        $this->paymentsFactory = $paymentsFactory;
        $this->paymentsRepository = $paymentsRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Look up an existing payment audit row by ipgTransactionId, or return a fresh model
     * so webhook retries from the gateway update the same row instead of inserting duplicates.
     *
     * @param string $transactionId
     * @return \AutifyDigital\LloydscardnetPayment\Model\Payments
     */
    private function findOrCreatePaymentForTransaction(string $transactionId)
    {
        if ($transactionId !== '') {
            /** @var \AutifyDigital\LloydscardnetPayment\Model\Payments $existing */
            $existing = $this->paymentsCollectionFactory->create()
                ->addFieldToFilter('ipgTransactionId', $transactionId)
                ->setPageSize(1)
                ->getFirstItem();
            if ($existing && $existing->getId()) {
                return $existing;
            }
        }
        return $this->paymentsFactory->create();
    }
    
    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Process webhook notification
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helper->addLog('Webhook Start');
        /** @var JsonResult $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        try {
            $request = $this->getRequest();
            if (!$request instanceof HttpRequest) {
                throw new LocalizedException(__("Invalid request type"));
            }
            
            $payload = $request->getContent();
            $webhookData = $this->jsonSerializer->unserialize($payload);
            
            if (!$webhookData) {
                throw new LocalizedException(__("Invalid webhook data"));
            }
            
            $this->helper->addLog('Webhook received: ' . $this->jsonSerializer->serialize($webhookData));
            
            $checkoutId = $webhookData['checkoutId'] ?? null;
            if (!$checkoutId) {
                throw new LocalizedException(__("Checkout ID not provided in webhook data"));
            }
            
            $order = $this->findOrderByCheckoutId($checkoutId);
            
            if (!$order) {
                throw new LocalizedException(__("Order not found for checkout ID: " . $checkoutId));
            }
            
            $transactionStatus = $webhookData['transactionStatus'] ?? '';
            $webhookTransactionId = $webhookData['ipgTransactionDetails']['ipgTransactionId'] ?? '';
            $paymentModel = $this->findOrCreatePaymentForTransaction((string) $webhookTransactionId);
            
            if ($transactionStatus === 'APPROVED') {
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $order->getPayment();
                $transactionId = $webhookData['ipgTransactionDetails']['ipgTransactionId'] ?? '';
                
                $payment->setTransactionId($transactionId)
                    ->setLastTransId($transactionId)
                    ->setAdditionalInformation('transaction_details', $this->jsonSerializer->serialize($webhookData))
                    ->setIsTransactionClosed(false);
                
                $brand = $last4 = '';
                
                if (isset($webhookData['tokenDetails']) && $order->getCustomerId()) {
                    $tokenValue = $webhookData['tokenDetails']['value'] ?? '';
                    $schemeTransactionId = $webhookData['tokenDetails']['schemeTransactionId'] ?? null;
                    $maskedCardNumber = $webhookData['tokenDetails']['cardNumber'] ?? '';
                    $brand = $webhookData['tokenDetails']['brand'] ?? '';
                    $last4 = substr($maskedCardNumber, -4);
                    $expMonth = $webhookData['paymentMethodUsed']['cards']['expiryDate']['month'] ?? '';
                    $expYear = $webhookData['paymentMethodUsed']['cards']['expiryDate']['year'] ?? '';
                    
                    $saveCardInfo = $payment->getAdditionalInformation();
                    $saveCard = isset($saveCardInfo['save_card']) ? $saveCardInfo['save_card'] : false;
                    
                    if ($saveCard) {
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

                        if ($schemeTransactionId !== null && !empty($schemeTransactionId)) {
                            $paymentTokenData['scheme_transaction_id'] = $schemeTransactionId;
                        }

                        $this->helper->savePaymentToken($paymentTokenData);
                    }
                }
                $jsonWebhookData = $this->jsonSerializer->serialize($webhookData);
                $transaction = $this->transactionBuilder
                    ->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($transactionId)
                    ->setAdditionalInformation(
                        ['transaction_details' => $jsonWebhookData]
                    )
                    ->setFailSafe(true)
                    ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
                
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->addCommentToStatusHistory(
                        (string) __('Payment approved. Transaction ID: %1', $transactionId)
                    );
                    
                if ($order->canInvoice()) {
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    $invoice->setData('requested_capture_case', \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    
                    $transactionSave = $this->dbTransaction
                        ->addObject($invoice)
                        ->addObject($order)
                        ->save();
                }
                
                $this->orderRepository->save($order);
                $total = number_format((float)$order->getGrandTotal(), 2, '.', '');
                $response = $webhookData['ipgTransactionDetails'];
                $approvalCode = $response['approvalCode'];
                $transactionStatus = $response['transactionStatus'];
                $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $response['ipgTransactionId'];

                $paymentModel->setData('amount', $total);
                $paymentModel->setData('order_id', $order->getEntityId());
                $paymentModel->setData('order_increment_id', $order->getIncrementId());
                $paymentModel->setData('cardnet_order_id', $order->getIncrementId());
                $paymentModel->setData('remote_reference', $response['ipgTransactionId']);
                
                $saveCardInfo = $payment->getAdditionalInformation();
                $saveCard = isset($saveCardInfo['save_card']) ? $saveCardInfo['save_card'] : false;
                $paymentModel->setData('save_card', $saveCard);
                
                $paymentModel->setData('remote_status_or_code', $transactionStatus);
                $paymentModel->setData('approval_code', $approvalCode);
                $paymentModel->setData('brand', $brand);
                $paymentModel->setData('last4', $last4);
                $paymentModel->setData('ipgTransactionId', $response['ipgTransactionId']);
                $paymentModel->setData('remote_message', $transactionMessage);
                $paymentModel->setData('status', 2);
                $this->paymentsRepository->save($paymentModel);
            } elseif ($transactionStatus === 'DECLINED' ||
                $transactionStatus === 'ERROR' ||
                $transactionStatus == 'VALIDATION_FAILED'
            ) {
                $response = $webhookData['ipgTransactionDetails'];
                $approvalCode = $response['approvalCode'];
                $total = number_format(floatval($order->getGrandTotal()), 2, '.', '');
                $transactionStatus = $response['transactionStatus'];
                $paymentModel->setData('amount', $total);
                $paymentModel->setData('order_id', $order->getEntityId());
                $paymentModel->setData('order_increment_id', $order->getIncrementId());
                $paymentModel->setData('cardnet_order_id', $order->getIncrementId());
                $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $response['ipgTransactionId'];
                $paymentModel->setData('remote_reference', $response['ipgTransactionId']);
                $paymentModel->setData('remote_status_or_code', $transactionStatus);
                $paymentModel->setData('approval_code', $approvalCode);
                $paymentModel->setData('remote_message', $transactionMessage);
                $paymentModel->setData('status', 4);
                $this->paymentsRepository->save($paymentModel);
                // Cancel order if order declined payment
                if ($order->canCancel()) {
                    $order->cancel()
                        ->addCommentToStatusHistory(
                            (string) __(
                                'Payment %1. Reason: %2',
                                $transactionStatus,
                                $webhookData['ipgTransactionDetails']['responseMessage'] ?? 'Not provided'
                            )
                        );
                    $this->orderRepository->save($order);
                }
            }
            
            $this->helper->addLog('Webhook Ended');
            return $resultJson->setData(['success' => true]);
        } catch (\Exception $e) {
            $this->helper->addLog('Webhook error: ' . $e->getMessage());
            return $resultJson->setData(['error' => 'Something went wrong']);
        }
    }
    
    /**
     * Find order by checkout ID using Magento's collection methods
     *
     * @param string $checkoutId
     * @return Order|null
     */
    private function findOrderByCheckoutId(string $checkoutId): ?Order
    {
        try {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $collection */
            $collection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('fiserv_checkout_id', $checkoutId)
                ->setPageSize(1);

            if ($collection->getSize() > 0) {
                /** @var Order $orderItem */
                $orderItem = $collection->getFirstItem();
                if ($orderItem->getEntityId()) {
                    return $orderItem;
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->helper->addLog('Error finding order by checkout ID: ' . $e->getMessage());
            return null;
        }
    }
}
