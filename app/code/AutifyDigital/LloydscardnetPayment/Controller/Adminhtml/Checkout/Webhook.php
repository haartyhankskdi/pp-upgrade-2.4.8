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
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\JsonFactory;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Webhook controller handles payment gateway callbacks
 * Works for both frontend and admin orders
 */
class Webhook implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Constructor
     *
     * @param JsonFactory $resultJsonFactory
     * @param HttpRequest $request
     * @param Data $helper
     * @param OrderFactory $orderFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        HttpRequest $request,
        Data $helper,
        OrderFactory $orderFactory,
        OrderCollectionFactory $orderCollectionFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->helper = $helper;
        $this->orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
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
     * Execute webhook handler
     * Handles webhooks for both frontend and admin orders
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->helper->addLog('Webhook Start - MOTO Checkout Solution');
        $result = $this->resultJsonFactory->create();
        
        try {
            $webhookData = $this->helper->getJsonDecode($this->request->getContent());
            
            if (!$webhookData) {
                $this->helper->addLog('Webhook Error: Invalid JSON data received');
                return $result->setData(['success' => false, 'message' => 'Invalid JSON data']);
            }

            $this->helper->addLog('Webhook received: ' . $this->helper->getJsonEncode($webhookData));

            $checkoutId = $webhookData['checkoutId'] ?? null;
            $transactionStatus = $webhookData['transactionStatus'] ?? null;
            $retryNumber = ($webhookData['retryNumber'] ?? 0);

            if (!$checkoutId) {
                $this->helper->addLog('Webhook Error: Missing checkoutId');
                return $result->setData(['success' => false, 'message' => 'Missing checkoutId']);
            }

            $order = $this->getOrderByCheckoutId($checkoutId);

            if (!$order || !$order->getEntityId()) {
                $this->helper->addLog("Webhook Error: Order not found for checkout ID: {$checkoutId}");
                return $result->setData(['success' => false, 'message' => 'Order not found']);
            }

            $orderReference = $order->getIncrementId();

            /** @var Payment $payment */
            $payment = $order->getPayment();
            $isAdminOrder = false;
            if ($payment) {
                $additionalInfo = $payment->getAdditionalInformation();
                $isAdminOrder = (bool)($additionalInfo['admin_order'] ?? false);
            }
            
            $orderType = $isAdminOrder ? 'Admin' : 'Frontend';
            $this->helper->addLog("Processing webhook for {$orderType} Order: {$orderReference}");

            $apiResponse = $this->helper->getCheckoutDetailsById($checkoutId, $order->getStoreId());

            if (!$apiResponse || $apiResponse['status'] !== 'success') {
                $order->addCommentToStatusHistory(
                    "{$orderType} order - Webhook received but API verification failed. Retry {$retryNumber}. Webhook Status: {$transactionStatus}"
                );
                $this->orderRepository->save($order);
                return $result->setData(['success' => false, 'message' => 'API verification failed']);
            }

            $apiCheckoutData = $apiResponse['response'] ?? $apiResponse['data'] ?? [];
            $apiTransactionStatus = $apiCheckoutData['transactionStatus'] ?? $apiCheckoutData['status'] ?? null;

            if ($apiTransactionStatus && $apiTransactionStatus !== $transactionStatus) {
                $this->helper->addLog("Webhook Warning: Status mismatch for {$orderType} Order {$orderReference}. Webhook: {$transactionStatus}, API: {$apiTransactionStatus}");
                $webhookData['transactionStatus'] = $apiTransactionStatus;
                $apiCheckoutData['transactionStatus'] = $apiTransactionStatus;
            }

            $this->processWebhookStatus($order, $webhookData, $apiCheckoutData, $retryNumber, $isAdminOrder);

            return $result->setData(['success' => true, 'message' => 'Webhook processed successfully']);

        } catch (\Exception $e) {
            $this->helper->addLog('Webhook Exception: ' . $e->getMessage());
            return $result->setData(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
        }
    }

    /**
     * Get order by fiserv checkout ID
     *
     * @param string $checkoutId
     * @return Order|null
     */
    protected function getOrderByCheckoutId($checkoutId): ?Order
    {
        try {
            $collection = $this->orderCollectionFactory->create();
            $collection->addFieldToFilter('fiserv_checkout_id', $checkoutId);
            $collection->setPageSize(1);

            /** @var Order $order */
            $order = $collection->getFirstItem();
            if ($order->getEntityId()) {
                return $order;
            }
            return null;
        } catch (\Exception $e) {
            $this->helper->addLog("Error finding order by checkout ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process webhook based on status
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $webhookData
     * @param array $apiCheckoutData
     * @param int $retryNumber
     * @param bool $isAdminOrder
     * @return void
     */
    protected function processWebhookStatus($order, $webhookData, $apiCheckoutData, $retryNumber, $isAdminOrder = false)
    {
        $status = $webhookData['transactionStatus'] ?? null;
        $orderReference = $order->getIncrementId();
        $orderType = $isAdminOrder ? 'Admin' : 'Frontend';

        $this->helper->addLog("Processing webhook status '{$status}' for {$orderType} Order {$orderReference}, retry {$retryNumber}");

        if ($status === 'COMPLETED' || $status === 'APPROVED' || $status === 'SUCCESS') {
            $this->handleSuccessfulPayment($order, $webhookData, $apiCheckoutData, $isAdminOrder);
        } elseif ($status === 'FAILED' || $status === 'DECLINED') {
            $this->handleFailedPayment($order, $webhookData, $apiCheckoutData, $isAdminOrder);
        } elseif ($status === 'CANCELLED' || $status === 'EXPIRED') {
            $this->handleCancelledPayment($order, $webhookData, $apiCheckoutData, $isAdminOrder);
        } elseif ($status === 'VALIDATION_FAILED') {
            $this->handleValidationFailed($order, $webhookData, $apiCheckoutData, $retryNumber, $isAdminOrder);
        } else {
            $commentPrefix = $isAdminOrder ? 'Admin order - ' : '';
            $order->addCommentToStatusHistory(
                "{$commentPrefix}Webhook received with unknown status: {$status}. Attempt - {$retryNumber}"
            );
            $this->orderRepository->save($order);
        }
    }

    /**
     * Handle successful payment
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $webhookData
     * @param array $apiCheckoutData
     * @param bool $isAdminOrder
     * @return void
     */
    protected function handleSuccessfulPayment($order, $webhookData, $apiCheckoutData, $isAdminOrder = false)
    {
        $orderReference = $order->getIncrementId();
        $orderType = $isAdminOrder ? 'Admin' : 'Frontend';
        $approvalCode = $apiCheckoutData['approvalCode']
                        ?? $webhookData['approvalCode']
                        ?? null;
        $status = $webhookData['transactionStatus'] ?? null;

        try {
            if ($this->helper->startsWith($approvalCode, 'Y:')) {
                if ($order->getState() === Order::STATE_PENDING_PAYMENT ||
                    $order->getStatus() === 'pending') {

                    /** @var Payment $payment */
                    $payment = $order->getPayment();
                    $transactionId = $apiCheckoutData['ipgTransactionId'] ??
                                    $webhookData['ipgTransactionDetails']['ipgTransactionId'] ??
                                    $apiCheckoutData['transactionId'] ??
                                    $webhookData['transactionId'] ??
                                    null;

                    if ($transactionId && $payment) {
                        $payment->setTransactionId($transactionId);
                        $payment->setIsTransactionClosed(false);
                        $payment->registerCaptureNotification($order->getGrandTotal());
                    }

                    $order->setState(Order::STATE_PROCESSING);
                    $order->setStatus(Order::STATE_PROCESSING);
                    $commentPrefix = $isAdminOrder ? 'Admin order - ' : '';
                    $order->addCommentToStatusHistory("{$commentPrefix}Payment completed successfully via webhook");
                    $this->orderRepository->save($order);

                    $this->helper->addLog("{$orderType} Order {$orderReference} updated to processing via webhook");
                }
            }
        } catch (\Exception $e) {
            $this->helper->addLog("Webhook Error: Failed to update {$orderType} Order {$orderReference}: " . $e->getMessage());
        }
    }

    /**
     * Handle failed payment
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $webhookData
     * @param array $apiCheckoutData
     * @param bool $isAdminOrder
     * @return void
     */
    protected function handleFailedPayment($order, $webhookData, $apiCheckoutData, $isAdminOrder = false)
    {
        $orderReference = $order->getIncrementId();
        $orderType = $isAdminOrder ? 'Admin' : 'Frontend';
        $failureReason = $webhookData['transactionFailure']['reason'] ??
                         $apiCheckoutData['failureReason'] ??
                         'Unknown';

        try {
            if ($order->canCancel()) {
                $order->cancel();
                $commentPrefix = $isAdminOrder ? 'Admin order - ' : '';
                $order->addCommentToStatusHistory("{$commentPrefix}Payment failed via webhook. Reason: {$failureReason}");
                $this->orderRepository->save($order);
                $this->helper->addLog("Webhook: {$orderType} Order {$orderReference} cancelled due to payment failure");
            }
        } catch (\Exception $e) {
            $this->helper->addLog("Webhook Error: Failed to cancel {$orderType} Order {$orderReference}: " . $e->getMessage());
        }
    }

    /**
     * Handle cancelled/expired payment
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $webhookData
     * @param array $apiCheckoutData
     * @param bool $isAdminOrder
     * @return void
     */
    protected function handleCancelledPayment($order, $webhookData, $apiCheckoutData, $isAdminOrder = false)
    {
        $orderReference = $order->getIncrementId();
        $orderType = $isAdminOrder ? 'Admin' : 'Frontend';
        $status = $webhookData['transactionStatus'] ?? 'CANCELLED';

        try {
            if ($order->canCancel()) {
                $order->cancel();
                $commentPrefix = $isAdminOrder ? 'Admin order - ' : '';
                $order->addCommentToStatusHistory("{$commentPrefix}Payment {$status} via webhook");
                $this->orderRepository->save($order);
                $this->helper->addLog("Webhook: {$orderType} Order {$orderReference} cancelled (status: {$status})");
            }
        } catch (\Exception $e) {
            $this->helper->addLog("Webhook Error: Failed to cancel {$orderType} Order {$orderReference}: " . $e->getMessage());
        }
    }

    /**
     * Handle validation failed status with retry logic
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $webhookData
     * @param array $apiCheckoutData
     * @param int $retryNumber
     * @param bool $isAdminOrder
     * @return void
     */
    protected function handleValidationFailed($order, $webhookData, $apiCheckoutData, $retryNumber, $isAdminOrder = false)
    {
        $orderReference = $order->getIncrementId();
        $orderType = $isAdminOrder ? 'Admin' : 'Frontend';
        $commentPrefix = $isAdminOrder ? 'Admin order - ' : '';
        
        $validationError = 'Unknown validation error';
        if (isset($webhookData['transactionFailure']['reason'])) {
            $validationError = $webhookData['transactionFailure']['reason'];
        }
        if (isset($webhookData['tokenDetails']['error']['message'])) {
            $validationError .= ' - ' . $webhookData['tokenDetails']['error']['message'];
        }
        if (isset($webhookData['tokenDetails']['error']['details'])) {
            $details = $this->helper->getJsonEncode($webhookData['tokenDetails']['error']['details']);
            $validationError .= ' - Details: ' . $details;
        }

        if ($retryNumber < 3) {
            $comment = sprintf(
                "{$commentPrefix}Payment validation failed (Attempt %d of 3). Error: %s. Waiting for retry.",
                $retryNumber,
                $validationError
            );

            try {
                $order->addCommentToStatusHistory($comment);
                $this->orderRepository->save($order);
                $this->helper->addLog("Webhook: {$orderType} Order {$orderReference} - Comment added for attempt " . ($retryNumber));
            } catch (\Exception $e) {
                $this->helper->addLog("Webhook Error: Failed to add comment to {$orderType} Order {$orderReference}: " . $e->getMessage());
            }

        } else {
            $comment = sprintf(
                "{$commentPrefix}Payment validation failed after %d attempts. Error: %s. Cancelling order.",
                $retryNumber,
                $validationError
            );

            try {
                if ($order->canCancel()) {
                    $order->cancel();
                    $order->addCommentToStatusHistory($comment);
                    $this->orderRepository->save($order);
                    $this->helper->addLog("Webhook: {$orderType} Order {$orderReference} cancelled after {$retryNumber} validation failures");
                } else {
                    $order->addCommentToStatusHistory($comment);
                    $this->orderRepository->save($order);
                    $this->helper->addLog("Webhook: {$orderType} Order {$orderReference} cannot be cancelled, comment added");
                }
            } catch (\Exception $e) {
                $this->helper->addLog("Webhook Error: Failed to process {$orderType} Order {$orderReference} after " . ($retryNumber) . " attempts: " . $e->getMessage());
            }
        }
    }
}
