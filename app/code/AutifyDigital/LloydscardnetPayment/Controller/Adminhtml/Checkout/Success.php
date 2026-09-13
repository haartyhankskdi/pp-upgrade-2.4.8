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
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Success extends Action implements HttpGetActionInterface, HttpPostActionInterface
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
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var PaymentsCollectionFactory
     */
    protected $paymentsCollectionFactory;

    /**
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Constructor
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param Config $config
     * @param Data $helper
     * @param Json $jsonSerializer
     * @param BuilderInterface $transactionBuilder
     * @param InvoiceService $invoiceService
     * @param DbTransaction $dbTransaction
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param PaymentsFactory $paymentsFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Config $config,
        Data $helper,
        Json $jsonSerializer,
        BuilderInterface $transactionBuilder,
        InvoiceService $invoiceService,
        DbTransaction $dbTransaction,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        PaymentsFactory $paymentsFactory,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        PaymentsRepositoryInterface $paymentsRepository,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->config = $config;
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
        $this->transactionBuilder = $transactionBuilder;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->paymentsFactory = $paymentsFactory;
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        $this->paymentsRepository = $paymentsRepository;
        $this->dateTime = $dateTime;
    }

    /**
     * Process successful payment response for admin order
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->helper->addlog('----MOTO Checkout Solution Success Callback Start----');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $data = $this->getRequest()->getParams();
            $this->helper->addlog('MOTO Checkout Solution Success callback received with params: ');
            $this->helper->addLog($data, true);

            $orderId = $this->getRequest()->getParam('order_id');
            if (!$orderId) {
                throw new LocalizedException(__('Order ID not provided'));
            }

            $order = null;
            try {
                /** @var Order $order */
                $order = $this->orderRepository->get($orderId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Order not found by ID, try by checkout ID below
            }

            if (!$order && isset($data['checkoutId'])) {
                /** @var Order $order */
                $order = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('fiserv_checkout_id', $data['checkoutId'])
                    ->setPageSize(1)
                    ->getFirstItem();
            }

            if (!$order || !$order->getEntityId()) {
                throw new LocalizedException(__('Order not found. Order ID: ' . $orderId));
            }

            $this->helper->addlog('Order found: ' . $order->getIncrementId());

            $checkoutId = $order->getData('fiserv_checkout_id') ?: ($data['checkoutId'] ?? null);
            if (!$checkoutId) {
                throw new LocalizedException(__('No checkout ID found for order: ' . $order->getIncrementId()));
            }

            if (!$order->getData('fiserv_checkout_id')) {
                $order->setData('fiserv_checkout_id', $checkoutId);
                $this->orderRepository->save($order);
            }

            $response = $this->helper->getCheckoutDetailsById($checkoutId, $order->getStoreId());
            $this->helper->addlog('Response from payment gateway:');
            $this->helper->addlog($response, true);

            if (!isset($response['status'], $response['response']) || $response['status'] !== 'success') {
                throw new LocalizedException(__('Failed to retrieve transaction details'));
            }

            $transactionDetails = (array)$response['response'];

            if (($transactionDetails['transactionStatus'] ?? '') === 'APPROVED') {
                $this->processSuccessfulPayment($order, $transactionDetails);
                $this->updatePaymentRecord($order, $transactionDetails, 2);

                $this->messageManager->addSuccessMessage(
                    __('Payment completed successfully for Order #%1', $order->getIncrementId())
                );
            } else {
                $errorMessage = $transactionDetails['errorMessage']
                    ?? $transactionDetails['ipgTransactionDetails']['transactionResult']
                    ?? 'Payment was not approved';

                $this->helper->addlog('Admin Payment not approved: ' . $errorMessage);
                $this->updatePaymentRecord($order, $transactionDetails, 4, $errorMessage);

                if ($order->canCancel()) {
                    $order->cancel();
                    $order->addCommentToStatusHistory('Admin order canceled: ' . $errorMessage);
                    $this->orderRepository->save($order);
                }

                $this->messageManager->addErrorMessage(__('Payment was not approved: %1', $errorMessage));
            }

            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);

        } catch (\Exception $e) {
            $this->helper->addlog('Error in admin success controller: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Error processing payment: %1', $e->getMessage()));

            return $resultRedirect->setPath('sales/order/index');
        } finally {
            $this->helper->addlog('----MOTO Checkout Solution Success Callback End----');
        }
    }

    /**
     * Process successful payment
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $transactionDetails
     * @return void
     * @throws \Exception
     */
    private function processSuccessfulPayment($order, $transactionDetails)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $ipgDetails = $transactionDetails['ipgTransactionDetails'] ?? [];

        $transactionId = $ipgDetails['ipgTransactionId'] ?? '';
        $authCode = $ipgDetails['approvalCode'] ?? '';

        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);

        $additionalInfo = [
            'transaction_id' => $transactionId,
            'auth_code' => $authCode,
            'transaction_details' => $this->jsonSerializer->serialize($transactionDetails)
        ];

        $this->helper->addLog($payment->getData(), true);

        $cardData = $transactionDetails['paymentMethodUsed']['cards'] ?? [];
        if ($cardNumber = $cardData['cardNumber'] ?? '') {
            $payment->setCcLast4(substr($cardNumber, -4));
            $payment->setCcType($this->helper->detectCardType($cardNumber));
            $additionalInfo['cc_number_masked'] = $cardNumber;
        }

        if (isset($transactionDetails['approvedAmount']['total'])) {
            $additionalInfo['approved_amount'] = $transactionDetails['approvedAmount']['total'];
            $additionalInfo['currency'] = $transactionDetails['approvedAmount']['currency'] ?? '';
        }

        $existingInfo = $payment->getAdditionalInformation() ?: [];
        $payment->setAdditionalInformation(array_merge($existingInfo, $additionalInfo));

        $this->transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($transactionId)
            ->setAdditionalInformation([Transaction::RAW_DETAILS => $transactionDetails])
            ->setFailSafe(true)
            ->build(Transaction::TYPE_CAPTURE);

        $order->setState(Order::STATE_PROCESSING)
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));

        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register();

            $this->dbTransaction->addObject($invoice)->addObject($order)->save();
            $this->invoiceSender->send($invoice);
            $this->helper->addlog('Invoice created for admin order: ' . $order->getIncrementId());
        }

        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
            $order->setEmailSent(true);
        }

        $this->orderRepository->save($order);
        $this->helper->addlog('Admin Order ' . $order->getIncrementId() . ' payment processed successfully');
    }

    /**
     * Update payment record status
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $transactionDetails
     * @param int $status Status code (2 = processing, 4 = failed)
     * @param string $errorMessage Error message for failed payments
     * @return void
     */
    private function updatePaymentRecord($order, $transactionDetails, $status, $errorMessage = '')
    {
        try {
            $collection = $this->paymentsCollectionFactory->create()
                ->addFieldToFilter('order_id', $order->getId())
                ->addFieldToFilter('status', 1)
                ->setPageSize(1);

            if (!$collection->getSize()) {
                return;
            }

            /** @var \AutifyDigital\LloydscardnetPayment\Model\Payments $paymentRecord */
            $paymentRecord = $collection->getFirstItem();
            $ipgDetails = $transactionDetails['ipgTransactionDetails'] ?? [];

            $approvalCode = $ipgDetails['approvalCode'] ?? '';
            $transactionStatus = $ipgDetails['transactionStatus'] ?? '';
            $ipgTransactionId = $ipgDetails['ipgTransactionId'] ?? '';
            $AVSCodeArray = $this->helper->getAVSCode($approvalCode);

            $brand = '';
            $last4 = '';

            if (isset($transactionDetails['paymentMethodUsed']['cards'])) {
                $cardData = $transactionDetails['paymentMethodUsed']['cards'];
                $brand = $cardData['brand'] ?? '';
                $last4 = substr($cardData['cardNumber'] ?? '', -4);
            } elseif (isset($ipgDetails['paymentMethodDetails']['paymentCard'])) {
                $cardData = $ipgDetails['paymentMethodDetails']['paymentCard'];
                $brand = $cardData['brand'] ?? '';
                $last4 = $cardData['last4'] ?? '';
            }

            $paymentRecord->addData([
                'remote_reference' => $this->helper->generateMerchantTransactionId(),
                'remote_status_or_code' => $transactionStatus,
                'approval_code' => $approvalCode,
                'brand' => $brand,
                'last4' => $last4,
                'ipgTransactionId' => $ipgTransactionId,
                'remote_message' => "$approvalCode|$transactionStatus|$ipgTransactionId",
                'status' => $status,
                'updated_at' => $this->dateTime->date('Y-m-d H:i:s'),
                'street_match' => $AVSCodeArray['streetMatch'],
                'postcode_match' => $AVSCodeArray['postalCodeMatch'],
                'cvv_match' => $AVSCodeArray['cvvMatch']
            ]);

            // Add 3DS response message for failed payments
            if ($status === 4) {
                $paymentRecord->setData(
                    'response_3ds_code_message',
                    $ipgDetails['processor']['responseMessage'] ?? ''
                );
            }

            $this->paymentsRepository->save($paymentRecord);

            $statusText = $status === 2 ? 'processing' : 'failed';
            $this->helper->addLog("Admin payment record updated to $statusText status for order: "
                . $order->getIncrementId());
        } catch (\Exception $e) {
            $this->helper->addLog('Error updating admin payment record: ' . $e->getMessage());
        }
    }
}
