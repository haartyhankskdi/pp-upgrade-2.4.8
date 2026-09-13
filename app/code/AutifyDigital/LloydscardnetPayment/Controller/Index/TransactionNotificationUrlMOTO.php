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

namespace AutifyDigital\LloydscardnetPayment\Controller\Index;

use Magento\Framework\Controller\ResultInterface;

class TransactionNotificationUrlMOTO extends AbstractAction
{
    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * Execute
     */
    public function execute(): ResultInterface
    {
        sleep(10); // phpcs:ignore
        $this->helper->addLog('Redirect TransactionNotificationUrlMOTO Start');
        $this->helper->addLog($this->getRequest()->getParams(), true);

        $oidParam = $this->getRequest()->getParam('oid');
        $orderId = $this->helper->stripOrderIdSuffix($oidParam);
        $notificationHash = (null !== $this->getRequest()->getParam('notification_hash'))?
        $this->getRequest()->getParam('notification_hash'):'';

        if (!empty($orderId) && !empty($notificationHash)) {
            try {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('increment_id', $orderId)
                    ->setPageSize(1)
                    ->getFirstItem();
                $storeId = $order->getStoreId();

                $mode = $this->config->getConfig('payment/basic/lloyds_mode', $storeId);
                $config = $this->initializeReDirectPaymentParameters($mode, $storeId);

                $transactionTime = (null !== $this->getRequest()->getParam('txndatetime')) ?
                $this->getRequest()->getParam('txndatetime') : '';
                $approvalCode = (null !== $this->getRequest()->getParam('approval_code')) ?
                $this->getRequest()->getParam('approval_code') : '';
                $chargeTotal = (null !== $this->getRequest()->getParam('chargetotal')) ?
                $this->getRequest()->getParam('chargetotal') : '';
                $currency = (null !== $this->getRequest()->getParam('currency')) ?
                $this->getRequest()->getParam('currency') : '';
                $merchantTransactionId = (null !== $this->getRequest()->getParam('merchantTransactionId')) ?
                $this->getRequest()->getParam('merchantTransactionId') : '';
                
                $status = (null !== $this->getRequest()->getParam('status')) ?
                $this->getRequest()->getParam('status') : '';
                $response_code_3dsecure = (null !== $this->getRequest()->getParam('response_code_3dsecure')) ?
                $this->getRequest()->getParam('response_code_3dsecure') : '';
                $response_3ds_code_message = $this->helper->getResponseMessage($response_code_3dsecure);
                
                $lloydsOrderId = (null !== $this->getRequest()->getParam('oid')) ?
                $this->getRequest()->getParam('oid') : '';

                $ipgTransactionId = (null !== $this->getRequest()->getParam('ipgTransactionId')) ?
                $this->getRequest()->getParam('ipgTransactionId'):null;
                $brand = (null !== $this->getRequest()->getParam('ccbrand')) ?
                $this->getRequest()->getParam('ccbrand'):null;
                $cardnumber = (null !== $this->getRequest()->getParam('cardnumber')) ?
                substr($this->getRequest()->getParam('cardnumber'), -4):null;
                
                $AVSCodeArray = $this->helper->getAVSCode($approvalCode);
                $verifyResponse = $this->helper->verifyResponseNotification(
                    $notificationHash,
                    $transactionTime,
                    $approvalCode,
                    $chargeTotal,
                    $currency,
                    $config['store_id'],
                    $storeId
                );
                $paymentModel = $this->helper->getPaymentByOrderId($order->getId());
                $transactionUpdateWebHook = $paymentModel->getTransactionUpdateWebhook();
 
                if ($transactionUpdateWebHook != 1) {
                    $order->addStatusHistoryComment(
                        'The transaction notification URL has been sent for this order. - MOTO HPP',
                        false
                    );
                    $this->orderRepository->save($order);
                    $paymentModel->setData('remote_status_or_code', $status); // Payment Status
                    $paymentModel->setData('approval_code', $approvalCode);
                    $paymentModel->setData('response_3ds_code_message', $response_code_3dsecure);
                    $paymentModel->setData('brand', $brand);
                    $paymentModel->setData('last4', $cardnumber);
                    $paymentModel->setData('ipgTransactionId', $ipgTransactionId);
                    
                    $paymentModel->setData('remote_message', $approvalCode.'|'.$status.'|'.$response_3ds_code_message.'|'.$lloydsOrderId); // phpcs:ignore
                    $paymentModel->setData('cardnet_order_id', $lloydsOrderId);
                    $paymentModel->setData('transaction_update_webhook', 1);
                    $paymentModel->setData('street_match', $AVSCodeArray ? $AVSCodeArray['streetMatch'] : 'N');
                    $paymentModel->setData('postcode_match', $AVSCodeArray ? $AVSCodeArray['postalCodeMatch'] : 'N');
                    $paymentModel->setData('cvv_match', $AVSCodeArray ? $AVSCodeArray['cvvMatch'] : 'N');
                    $paymentModel->save();

                    $redirectEmailSent = $paymentModel->getRedirectEmailSent();

                    if ($verifyResponse && ($this->helper->startsWith($approvalCode, 'Y:')
                        || strpos(strtolower($approvalCode), 'waiting 3dsecure') !== false)
                        && $status === 'APPROVED') {
                        try {
                            $order = $this->helper->processOrder($order, $redirectEmailSent);
                            $paymentModel->setData('status', 2);
                            $paymentModel->save();
                        } catch (\Exception $ex) {
                            $this->helper->addLog('Re Direct Response Exception: '.$ex->getMessage());
                        }

                        /** "last successful quote" */
                        $this->checkoutSession->setLastQuoteId($order->getQuoteId())
                            ->setLastSuccessQuoteId($order->getQuoteId());
                        $this->checkoutSession->setLastOrderId($order->getId())
                            ->setLastRealOrderId($order->getIncrementId())
                            ->setLastOrderStatus($order->getStatus());
                    } elseif (strpos(strtolower($approvalCode), 'cancel') !== false) {
                        $paymentModel->setData('status', 3);
                        $paymentModel->save();

                        // Payment Cancelled
                        if ($order->canCancel()) {
                            $this->helper->cancelOrder($order);
                        }
                    } else {
                        $paymentModel->setData('status', 4);
                        $paymentModel->save();

                        // Payment Cancelled
                        if ($order->canCancel()) {
                            $this->helper->cancelOrder($order);
                        }
                    }
                } else {
                    $this->helper->addLog('TransactionNotificationUrlMOTO already processed');
                }
            } catch (\Exception $e) {
                $this->helper->addLog('TransactionNotificationUrlMOTO Exception: '.$e->getMessage());
            }
        }
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
        return $result;
    }
}
