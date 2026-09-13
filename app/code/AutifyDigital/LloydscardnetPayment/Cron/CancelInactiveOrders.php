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

namespace AutifyDigital\LloydscardnetPayment\Cron;

use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;

class CancelInactiveOrders
{
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var Data
     */
    protected $autifyDigitalHelper;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * @var array
     */
    public const PAYMENT_METHODS = [
        'lcnetredirect',
        'lcnetpaymentjs',
        'cardnetapplepay',
        'cardnetgooglepay',
        'lbopcheckoutsolution'
    ];
    
    /**
     * @var array
     */
    public const CANCELLABLE_STATUSES = ['pending', 'payment_pending', 'waiting', 'cancelled', 'declined', 'error', 'failed'];

    /**
     * Constructor
     *
     * @param Data $autifyDigitalHelper
     * @param TimezoneInterface $timezoneInterface
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param JsonHelper $jsonHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     */
    public function __construct(
        Data $autifyDigitalHelper,
        TimezoneInterface $timezoneInterface,
        OrderCollectionFactory $orderCollectionFactory,
        JsonHelper $jsonHelper,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository
    ) {
        $this->autifyDigitalHelper = $autifyDigitalHelper;
        $this->timezoneInterface = $timezoneInterface;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->jsonHelper = $jsonHelper;
        $this->orderRepository = $orderRepository;
        $this->paymentsRepository = $paymentsRepository;
    }

    /**
     * Cron to cancel inactive orders
     *
     * This cron will cancel orders that have been in a pending state for more than 30 minutes.
     * It will only cancel orders that are using one of the payment methods defined in PAYMENT_METHODS constant.
     *
     * @return void
     */
    public function execute()
    {
        $this->autifyDigitalHelper->addLog('Cron Cancel Inactive Orders');
    
        try {
            $utcDate = $this->timezoneInterface->date(null, null, false);
            $utcDate->modify('-30 minutes');
            $timedurations = $utcDate->format('Y-m-d H:i:s');
            
            $orders = $this->getOrders($timedurations);
    
            foreach ($orders as $order) {
                $cancelData = $this->shouldCancelOrder($order);
                if ($cancelData !== false) {
                    if ($this->cancelOrder($order, $cancelData)) {
                        $this->autifyDigitalHelper->addLog("Order cancelled: " . $order->getId());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->autifyDigitalHelper->addLog("Error: " . $e->getTraceAsString());
        }
    }

    /**
     * Retrieve a collection of orders that are in a pending state and have been created
     * before the given date. The orders must also have a payment method that is one of
     * the payment methods defined in the PAYMENT_METHODS constant.
     *
     * @param string $beforeDate
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected function getOrders($beforeDate)
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('created_at', ['lt' => $beforeDate]);

        $collection->getSelect()->join(
            ['payment' => $collection->getTable('sales_order_payment')],
            'main_table.entity_id = payment.parent_id',
            []
        );
        $collection->addFieldToFilter('status', ['in' => ['pending', 'payment_pending']]);
        $collection->addFieldToFilter('payment.method', ['in' => self::PAYMENT_METHODS]);
        $collection->setPageSize(1000);
        $collection->load();

        return $collection;
    }
    
    /**
     * Returns API response data if order should be cancelled, false otherwise
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array|false
     */
    protected function shouldCancelOrder($order)
    {
        try {
            $payment = $this->autifyDigitalHelper->getPaymentByOrderId($order->getId());
            if (!$payment) {
                return false;
            }
            
            $transactionId = $payment->getData('ipgTransactionId');

            if (!$transactionId) {
                return [];
            }
    
            if ($transactionId) {
                $transactionInfo = $this->autifyDigitalHelper->fetchTransactionInfo($transactionId, $order->getStoreId());
    
                if ($transactionInfo['httpCode'] == 200) {
                    $response = $this->jsonHelper->jsonDecode($transactionInfo['response']);
                    $status = $response['transactionResult'] ?? '';
                    
                    if (in_array(strtolower($status), self::CANCELLABLE_STATUSES)) {
                        return $response;
                    }
                }
            }
            return false;
        } catch (\Exception $e) {
            $this->autifyDigitalHelper->addLog("Error checking order status {$order->getId()}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel order and update payment data with API response
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $responseData
     * @return bool
     */
    protected function cancelOrder($order, $responseData = [])
    {
        try {
            if ($order->canCancel()) {
                $order->cancel();
                $order->addCommentToStatusHistory('Auto-cancelled due to 30+ min inactivity in pending status');
                $this->orderRepository->save($order);

                $payment = $this->autifyDigitalHelper->getPaymentByOrderId($order->getId());
                if ($payment && !empty($responseData)) {
                    $remoteStatus = $responseData['transactionState'] ?? 'CANCELLED';
                    $payment->setStatus(3);
                    $payment->setRemoteMessage($responseData['approvalCode'] ?? null);
                    $payment->setApprovalCode($responseData['approvalCode'] ?? null);
                    $payment->setRemoteStatusOrCode($remoteStatus);
                    $this->paymentsRepository->save($payment);
                }
                return true;
            }
        } catch (\Exception $e) {
            $this->autifyDigitalHelper->addLog("Error cancelling order {$order->getId()}: " . $e->getMessage());
        }
        return false;
    }
}
