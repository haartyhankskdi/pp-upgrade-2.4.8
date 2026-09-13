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
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory;
use Magento\Backend\Model\Session\Quote as AdminQuoteSession;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Failure extends Action implements HttpGetActionInterface, HttpPostActionInterface
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
     * @var Config
     */
    protected $config;

    /**
     * @var Data
     */
    protected $helper;

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
     * @var OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusHistoryRepository;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Constructor
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param AdminQuoteSession $adminQuoteSession
     * @param Config $config
     * @param Data $helper
     * @param Json $jsonSerializer
     * @param PaymentsFactory $paymentsFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        AdminQuoteSession $adminQuoteSession,
        Config $config,
        Data $helper,
        Json $jsonSerializer,
        PaymentsFactory $paymentsFactory,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        PaymentsRepositoryInterface $paymentsRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        DateTime $dateTime
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->adminQuoteSession = $adminQuoteSession;
        $this->config = $config;
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
        $this->paymentsFactory = $paymentsFactory;
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        $this->paymentsRepository = $paymentsRepository;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->dateTime = $dateTime;
    }

    /**
     * Process failed payment response for admin order
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $this->helper->addlog('Admin Failure callback received with params: ');
        $this->helper->addLog($data, true);

        try {
            $checkoutId = $this->getRequest()->getParam('checkoutId');
            $orderId = $this->getRequest()->getParam('order_id');

            // Try alternative checkout ID parameters
            if (!$checkoutId && !$orderId) {
                foreach (['checkout_id', 'id', 'reference', 'ref'] as $param) {
                    if ($value = $this->getRequest()->getParam($param)) {
                        $this->helper->addlog("Found checkout ID in alternative parameter: $param = $value");
                        $checkoutId = $value;
                        break;
                    }
                }
            }

            $this->helper->addlog('Admin Failure callback received. Checkout ID: ' . ($checkoutId ?: 'Not found') . 
                                  ', Order ID: ' . ($orderId ?: 'Not found'));

            // Load order with multiple fallback strategies
            $order = $this->loadOrder($orderId, $checkoutId);

            if (!$order || !$order->getId()) {
                throw new LocalizedException(__("Order not found"));
            }

            $this->helper->addlog('Order found: ' . $order->getIncrementId());

            $checkoutId = $checkoutId ?: $order->getData('fiserv_checkout_id');
            $this->helper->addlog('Retrieved checkout ID: ' . ($checkoutId ?: 'Not found'));

            $transactionDetails = [];
            if ($checkoutId) {
                $response = $this->helper->callFiservCurl(Data::FISERV_API_URL . "/{$checkoutId}", [], 'GET', $order->getStoreId());
                $this->helper->addlog($response, true);

                if (isset($response['status'], $response['response']) && $response['status'] === 'success') {
                    $transactionDetails = (array)$response['response'];
                    $this->helper->addlog('Transaction details retrieved successfully');
                    
                    $transactionStatus = $transactionDetails['transactionStatus'] ?? 'Not available';
                    $transactionResult = $transactionDetails['ipgTransactionDetails']['transactionResult'] ?? 'Not available';
                    
                    $this->helper->addlog("Transaction status: $transactionStatus");
                    $this->helper->addlog("Transaction result: $transactionResult");

                    $this->updatePaymentRecordToFailed($order, $transactionDetails);
                }
            }

            $failureReason = 'Payment failed';
            if (isset($transactionDetails['ipgTransactionDetails']['ipgTransactionId'])) {
                $failureReason .= ' (Transaction ID: ' . $transactionDetails['ipgTransactionDetails']['ipgTransactionId'] . ')';
            }

            $this->cancelOrder($order, $failureReason, $transactionDetails);

            $this->messageManager->addErrorMessage(
                __('Payment was not successful for Order #%1. Please try again or select another payment method.', 
                   $order->getIncrementId())
            );

            /** @var \Magento\Backend\Model\View\Result\Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('sales/order/view', ['order_id' => $order->getId()]);

        } catch (\Exception $e) {
            $this->helper->addlog('Error in admin failure controller: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->messageManager->addErrorMessage(__('There was an error processing the payment. Please try again.'));

            /** @var \Magento\Backend\Model\View\Result\Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('sales/order/index');
        }
    }

    /**
     * Load order with multiple fallback strategies
     *
     * @param int|null $orderId
     * @param string|null $checkoutId
     * @return \Magento\Sales\Model\Order|null
     */
    private function loadOrder($orderId, $checkoutId)
    {
        // Try loading by order ID
        if ($orderId) {
            try {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderRepository->get($orderId);
                $this->helper->addlog('Loaded order by ID: ' . $order->getIncrementId());
                return $order;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Continue to next strategy
            }
        }

        // Try loading by checkout ID
        if ($checkoutId) {
            $this->helper->addlog('Attempting to load order by checkout ID: ' . $checkoutId);

            $orderCollection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('fiserv_checkout_id', $checkoutId)
                ->setPageSize(1);

            if ($orderCollection->getSize()) {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $orderCollection->getFirstItem();
                $this->helper->addlog('Order found: ' . $order->getIncrementId());
                return $order;
            }

            // Try finding by payment additional info
            $this->helper->addlog('Trying to find order by payment additional info');
            $allOrders = $this->orderCollectionFactory->create()
                ->setPageSize(10)
                ->setOrder('created_at', 'DESC');

            foreach ($allOrders as $potentialOrder) {
                /** @var \Magento\Sales\Model\Order $potentialOrder */
                $payment = $potentialOrder->getPayment();
                if ($payment) {
                    $additionalInfo = $payment->getAdditionalInformation();
                    if (isset($additionalInfo['checkout_id']) && $additionalInfo['checkout_id'] === $checkoutId) {
                        $this->helper->addlog('Order found via payment additional info: ' . $potentialOrder->getIncrementId());
                        return $potentialOrder;
                    }
                }
            }
        }

        // Last resort - try admin session
        if ($sessionOrderId = $this->adminQuoteSession->getOrderId()) {
            try {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderRepository->get($sessionOrderId);
                $this->helper->addlog('Loaded order from admin session: ' . $order->getIncrementId());
                return $order;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Continue
            }
        }

        $this->helper->addlog('No order found');
        return null;
    }

    /**
     * Update payment record to failed status
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $transactionDetails
     * @return void
     */
    private function updatePaymentRecordToFailed($order, $transactionDetails)
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
            $transactionStatus = $ipgDetails['transactionStatus'] ?? 'Failed';
            $ipgTransactionId = $ipgDetails['ipgTransactionId'] ?? '';
            $AVSCodeArray = $this->helper->getAVSCode($approvalCode);

            $paymentRecord->addData([
                'remote_reference' => $this->helper->generateMerchantTransactionId(),
                'remote_status_or_code' => $transactionStatus,
                'approval_code' => $approvalCode,
                'remote_message' => "$approvalCode|$transactionStatus|$ipgTransactionId",
                'status' => 4,
                'updated_at' => $this->dateTime->date('Y-m-d H:i:s'),
                'street_match' => $AVSCodeArray['streetMatch'] ?? 'N',
                'postcode_match' => $AVSCodeArray['postalCodeMatch'] ?? 'N',
                'cvv_match' => $AVSCodeArray['cvvMatch'] ?? 'N'
            ]);

            $this->paymentsRepository->save($paymentRecord);

            $this->helper->addLog('Admin payment record updated to failed status for order: ' . $order->getIncrementId());
        } catch (\Exception $e) {
            $this->helper->addLog('Error updating admin payment record to failed: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order and save additional information
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $reason
     * @param array $additionalInfo
     * @return void
     */
    private function cancelOrder($order, $reason, $additionalInfo = [])
    {
        try {
            $this->helper->addlog('Attempting to cancel admin order: ' . $order->getIncrementId());

            if (!empty($additionalInfo)) {
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $order->getPayment();
                $ipgDetails = $additionalInfo['ipgTransactionDetails'] ?? [];
                $cardData = $additionalInfo['paymentMethodUsed']['cards'] ?? [];

                if (isset($ipgDetails['ipgTransactionId'])) {
                    $payment->setLastTransId($ipgDetails['ipgTransactionId']);
                    $this->helper->addlog('Transaction ID saved: ' . $ipgDetails['ipgTransactionId']);
                }

                $existingInfo = $payment->getAdditionalInformation() ?: [];
                $infoData = [
                    'transaction_details' => $this->jsonSerializer->serialize($additionalInfo)
                ];

                if (isset($additionalInfo['checkoutId'])) {
                    $infoData['checkout_id'] = $additionalInfo['checkoutId'];
                }
                if (isset($additionalInfo['transactionStatus'])) {
                    $infoData['transaction_status'] = $additionalInfo['transactionStatus'];
                }
                if (isset($cardData['cardNumber'])) {
                    $infoData['card_number'] = $cardData['cardNumber'];
                }
                if (isset($ipgDetails['approvalCode'])) {
                    $infoData['approval_code'] = $ipgDetails['approvalCode'];
                }

                $payment->setAdditionalInformation(array_merge($existingInfo, $infoData));
                $this->helper->addlog('Additional information saved to admin order payment');
            }

            if ($order->canCancel()) {
                $order->cancel();
                $order->addCommentToStatusHistory('Admin order - ' . $reason);
                $this->orderRepository->save($order);
                $this->helper->addlog('Admin Order ' . $order->getIncrementId() . ' canceled: ' . $reason);
            } else {
                $order->addCommentToStatusHistory(
                    'Admin order - Payment failed but order could not be automatically canceled. Reason: ' . $reason .
                    ' Current state: ' . $order->getState()
                );
                $this->orderRepository->save($order);
                $this->helper->addlog('Admin Order ' . $order->getIncrementId() .
                    ' cannot be canceled. Current state: ' . $order->getState());
            }
        } catch (\Exception $e) {
            $this->helper->addlog('Error canceling admin order: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
