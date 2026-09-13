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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Transaction;

use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Fetch extends \Magento\Backend\App\Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Data
     */
    protected $autifyDigitalHelper;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var array
     */
    private $statusArr = [
        'WAITING'   => 1,
        'PENDING'   => 1,
        'APPROVED'  => 2,
        'SUCCESS'   => 2,
        'CANCELLED' => 3,
        'DECLINED'  => 4,
        'ERROR'     => 4,
        'REFUNDED'  => 5,
    ];

    /**
     * @var array
     */
    private $cancelOrderStatuses = ['CANCELLED', 'DECLINED', 'ERROR'];

    /**
     * Fetch constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param Data $autifyDigitalHelper
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Data $autifyDigitalHelper,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        JsonHelper $jsonHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->autifyDigitalHelper = $autifyDigitalHelper;
        $this->redirect = $redirect;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    /**
     * Fetch transaction status and update record
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $this->autifyDigitalHelper->addLog('Fetch Transaction Call');
        $resultRedirect = $this->resultRedirectFactory->create();
        $ipgTransactionId = $this->getRequest()->getParam('transaction_id');
        if (!$ipgTransactionId) {
            $this->messageManager->addErrorMessage((string) __('Transaction ID is required.'));
            $this->autifyDigitalHelper->addLog('Transaction Id not found');
            return $resultRedirect->setPath($this->redirect->getRedirectUrl());
        }

        $getTransaction = $this->autifyDigitalHelper->getPaymentByIPGTransactionId($ipgTransactionId);

        if ($getTransaction !== null && $getTransaction->getStatus() == 1) {
            try {
                $transactionStoreId = null;
                if ($getTransaction->getOrderId()) {
                    $transactionStoreId = $this->orderRepository->get($getTransaction->getOrderId())
                        ->getStoreId();
                }
                $responseArr = $this->autifyDigitalHelper->fetchTransactionInfo($ipgTransactionId, $transactionStoreId);
                $httpCode = $responseArr['httpCode'];
                $response = $responseArr['response'];
                if ($httpCode == 200) {
                    $responseData = $this->jsonHelper->jsonDecode($response);
                    $this->autifyDigitalHelper->addLog($response);
                    if ($responseData) {
                        $remoteStatus = $responseData['transactionState'] ?? 'ERROR';
                        $status = $this->statusArr[$remoteStatus] ?? 4;

                        $getTransaction->setStatus($status);
                        $getTransaction->setAmount($responseData['transactionAmount']['total'] ?? 0);
                        $getTransaction->setIpgTransactionId($responseData['ipgTransactionId'] ?? null);
                        $getTransaction->setRemoteMessage($responseData['approvalCode'] ?? null);
                        $getTransaction->setApprovalCode($responseData['approvalCode'] ?? null);
                        $getTransaction->setRemoteStatusOrCode($remoteStatus);
                        $getTransaction->save();

                        if (in_array($remoteStatus, $this->cancelOrderStatuses)) {
                            $this->cancelOrder($getTransaction->getOrderId(), $remoteStatus);
                        }

                        $this->messageManager->addSuccessMessage((string) __('Transaction Info Fetched Successfully.'));
                    } else {
                        $this->messageManager->addErrorMessage((string) __('Invalid response format.'));
                    }
                } elseif ($httpCode == 404) {
                    $this->autifyDigitalHelper->addLog('Transaction not found. Please verify the transaction ID.');
                    $this->messageManager->addErrorMessage((string) __('Transaction not found. Please verify the transaction ID.'));
                } else {
                    $errorMessage = 'API Error: HTTP ' . $httpCode;
                    if ($response) {
                        $errorData = $this->jsonHelper->jsonDecode($response);
                        if (isset($errorData['error']['message'])) {
                            $errorMessage .= ' - ' . $errorData['error']['message'];
                        }
                    }
                    $this->autifyDigitalHelper->addLog($errorMessage);
                    $this->messageManager->addErrorMessage((string) __($errorMessage));
                    
                    $this->cancelOrder($getTransaction->getOrderId(), 'API_ERROR');
                }
            } catch (\Exception $e) {
                $this->autifyDigitalHelper->addLog($e->getMessage());
                $this->messageManager->addErrorMessage((string) __('An error occurred while fetching transaction status: %1', $e->getMessage()));
                
                $this->cancelOrder($getTransaction->getOrderId(), 'EXCEPTION_ERROR');
            }
        } else {
            $this->messageManager->addErrorMessage((string) __('The application is not in the database or not in valid status.'));
        }

        return $resultRedirect->setPath(
            'sales/order/view',
            ['order_id' => $getTransaction->getOrderId()]
        );
    }

    /**
     * Cancel order when payment fails
     *
     * @param int $orderId
     * @param string $reason
     * @return void
     */
    private function cancelOrder($orderId, $reason)
    {
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($orderId);
            
            if ($order->canCancel()) {
                $order->cancel();
                $order->addCommentToStatusHistory(
                    (string) __('Order cancelled due to payment failure. : %1', $reason),
                    false,
                    true
                );
                $this->orderRepository->save($order);
                
                $this->autifyDigitalHelper->addLog("Order #{$orderId} cancelled due to : {$reason}");
            } else {
                $this->autifyDigitalHelper->addLog("Order #{$orderId} cannot be cancelled (current state: {$order->getState()})");
            }
        } catch (\Exception $e) {
            $this->autifyDigitalHelper->addLog("Failed to cancel order #{$orderId}: " . $e->getMessage());
        }
    }
}
