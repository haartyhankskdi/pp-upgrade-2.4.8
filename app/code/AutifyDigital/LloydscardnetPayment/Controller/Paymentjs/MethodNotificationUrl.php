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

namespace AutifyDigital\LloydscardnetPayment\Controller\Paymentjs;

use AutifyDigital\LloydscardnetPayment\Controller\Index\AbstractAction;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order;

class MethodNotificationUrl extends AbstractAction
{
    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): ResultInterface
    {
        $orderId = (int) $this->getRequest()->getParam('order_id');
        $threeDSMethodData = $this->getRequest()->getParam('threeDSMethodData') ?
        'Yes' : 'No';

        $this->helper->addLog('PaymentJS MethodNotificationUrl Start');
        $this->helper->addLog($this->getRequest()->getParams(), true);

        $this->saveThreeDSMethodDataWithOrderId($threeDSMethodData, $orderId);

        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
        return $result;
    }
    /**
     * Save the 3DS Method Data and Order ID in the vault_payment_token table
     *
     * @param string $threeDSMethodData
     * @param int $orderId
     * @return void
     */
    private function saveThreeDSMethodDataWithOrderId($threeDSMethodData, $orderId): void
    {
        try {
            try {
                /** @var Order $order */
                $order = $this->orderRepository->get($orderId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $order = null;
            }

            if ($order && $order->getEntityId()) {
                $orderStatus = $order->getState();
                if ($orderStatus === \Magento\Sales\Model\Order::STATE_PROCESSING ||
                    $orderStatus === \Magento\Sales\Model\Order::STATE_COMPLETE) {
                    $this->helper->addLog('Order ' . $orderId . ' is in ' . $orderStatus . ' state. Skipping iframe_received update.');
                    return;
                }
            }

            /** @var \AutifyDigital\LloydscardnetPayment\Model\Payments $payment */
            $payment = $this->paymentsCollectionFactory->create()
                ->addFieldToFilter('order_id', $orderId)
                ->getFirstItem();

            if ($payment && $payment->getId()) {
                $currentCount = (int) $payment->getIframeReceived();
                $payment->setIframeReceived($currentCount + 1);
                $this->paymentsRepository->save($payment);
                $this->helper->addLog('Iframe received count updated to: ' . ($currentCount + 1) . ' for order: ' . $orderId);
            }
            
            $paymentDetailsArr['threeDSMethodData'] = $threeDSMethodData;
            $paymentTokens = $this->paymentTokenRepository->getList($this->searchCriteriaBuilder->create());
            foreach ($paymentTokens->getItems() as $paymentToken) {
                $details = $paymentToken->getTokenDetails();
                $detailsArray = $this->serializer->unserialize($details);

                if (isset($detailsArray['orderId']) && $detailsArray['orderId'] == $orderId) {
                    $updatedDetails = array_merge($detailsArray, $paymentDetailsArr); // phpcs:ignore
                    $vaultDetail = $this->serializer->serialize($updatedDetails);
                    $paymentToken->setTokenDetails($vaultDetail);

                    $this->paymentTokenRepository->save($paymentToken);
                    break;
                }
            }
        } catch (\Exception $e) {
             $this->helper->addLog($e->getMessage(), true);
        }
    }
}
