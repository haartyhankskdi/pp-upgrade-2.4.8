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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Payments;

use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;

class RedirectPostData extends AbstractAction
{
     /**
      * @var \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory
      */
    protected $lcPaymentsFactory;

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute() // phpcs:ignore
    {
        $this->helper->addLog('Admin RedirectPostData Controller call');
        $result = $this->resultJsonFactory->create();

        try {
            $orderId = $this->getRequest()->getParam('order_id');
            $this->helper->addLog('Admin RedirectPostData Order Id');
            $this->helper->addLog((string)$orderId);

            if (empty($orderId)) {
                throw new LocalizedException(__('Order ID is missing'));
            }

            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->getAdminOrder((int)$orderId);

            if (!$order->getEntityId()) {
                throw new LocalizedException(__('Order not found'));
            }

            $storeId = $order->getStoreId();
            $mode = $this->config->getConfig('payment/basic/lloyds_mode', $storeId);

            $config = $this->initializeAdminRedirectPaymentParameters($mode, $orderId, $storeId);

            $this->helper->addLog('Admin Order Id : ' . (string)$order->getId());

            try {
                $action = (string)($config['processing_url'] ?? '');
                $amount = (float)$order->getGrandTotal();
                if ($amount <= 0) {
                    $amount = (float)$order->getBaseGrandTotal();
                }
                $ccy_code = (string)$order->getOrderCurrencyCode();
                $transactionCode = (string)$order->getIncrementId() . '-' . (string)time();

                $order->setStatus('pending_payment');
                $this->orderRepository->save($order);

                $newTxCode = $this->helper->generateMerchantTransactionId();
                $currency = $this->helper->getIsoCurrencyCodeFromCurrencyCode($ccy_code);
              
                $chargeTotalAsFloat = round($amount, 2);
                $chargeTotalAsInt = (int)round($chargeTotalAsFloat * 100);
                $currencyStr = (string)$currency;

                $billingAddress = $order->getBillingAddress();
                $shippingAddress = $order->getShippingAddress();

                if (!$shippingAddress || $shippingAddress->getFirstname() == null) {
                    $shippingAddress = $billingAddress;
                }

                $timeZone = $this->helper->timezone();
                $dt = $timeZone->date();

                $transactionTime = $dt->format('Y:m:d-H:i:s');

                $billName = trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
                $shipName = trim($shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname());

                $email = (string)$billingAddress->getEmail();
                if (!$email) {
                    $email = (string)$order->getCustomerEmail();
                }

                $chargeTotal = number_format((float)$amount, 2, '.', '');
                $hashValue = $this->helper->createHash($config['store_id'], $transactionTime, $chargeTotal, $currency, $storeId);

                $payment = $order->getPayment();
                $methodCode = (string)$payment->getMethod();

                $enablePONumber = (int)$this->config->getConfig('payment/basic/active_po_number', $storeId);
                $configPONumber = (string)$this->config->getConfig('payment/basic/dynamic_data_po_number', $storeId);

                $configponumberarray = array_filter(explode('|', $configPONumber));
                $ponumber = '';

                if (count($configponumberarray) > 0 && $enablePONumber === 1) {
                    $keys = array_keys($configponumberarray);
                    $lastKey = end($keys);

                    foreach ($configponumberarray as $key => $configponumberval) {
                        $value = (string)$order->getData($configponumberval);
                        if ($key === $lastKey) {
                            $ponumber .= $value;
                        } else {
                            if ($value !== '') {
                                $ponumber .= $value . '_';
                            }
                        }
                    }
                    if ($ponumber !== '') {
                        $ponumber = substr($ponumber, 0, 49);
                    }
                }

                $adminSessionId = '';
                if ($this->_auth->isLoggedIn()) {
                    /** @var \Magento\User\Model\User $adminUser */
                    $adminUser = $this->_auth->getUser();
                    $adminSessionId = (string)$adminUser->getId();
                }

                $responseFailURL = (string)($config['return_url'] ?? '') . '?order_id=' . (string)$order->getId();
                $responseSuccessURL = (string)($config['return_url'] ?? '') . '?order_id=' . (string)$order->getId();

                $formData = [
                    'txntype' => 'sale',
                    'trxOrigin' => 'PHONE',
                    'timezone' => date_default_timezone_get(),
                    'txndatetime' => $transactionTime,
                    'hash_algorithm' => 'SHA256',
                    'hash' => $hashValue,
                    'oid' => (string)$this->helper->getOrderIdWithSuffix($order->getIncrementId()),
                    'storename' => (string)($config['store_id'] ?? ''),
                    'comments' => 'AutifyDigital LBOP Magento Admin MOTO HPP',
                    'chargetotal' => $chargeTotal,
                    'currency' => $currencyStr,
                    'merchantTransactionId' => $newTxCode,
                    'responseFailURL' => $responseFailURL,
                    'responseSuccessURL' => $responseSuccessURL,
                    'transactionNotificationURL' => (string)($config['transaction_notification_url'] ?? ''),
                    'checkoutoption' => 'combinedpage'
                ];

                $this->helper->addLog('Admin Redirect formData: ' . $this->helper->getJsonEncode($formData));

                $postData = [
                    'action' => $action,
                    'error' => false,
                    'fields' => $formData,
                    'is_admin' => true
                ];

                $lcPaymentModel = $this->lcPaymentsFactory->create();
                $lcPaymentModel->setData('payment_method', 'lcnetredirect');
                $lcPaymentModel->setData('amount', $chargeTotal);
                $lcPaymentModel->setData('status', 1);
                $lcPaymentModel->setData('order_id', $order->getId());
                $lcPaymentModel->setData('order_increment_id', $order->getIncrementId());
                $lcPaymentModel->setData('remote_reference', $newTxCode);
                $lcPaymentModel->setData('is_admin', 1);
                $this->paymentsRepository->save($lcPaymentModel);

                return $result->setData($postData);
            } catch (\Exception $e) {
                $this->helper->addLog('Admin Redirect Order Exception: ' . (string)$e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            $this->helper->addLog('Admin Payment Exception: ' . (string)$e->getMessage());
            $this->messageManager->addErrorMessage((string)$e->getMessage());

            $postData = [
                'action' => '',
                'error' => true,
                'message' => (string)$e->getMessage(),
                'fields' => []
            ];

            return $result->setData($postData);
        }
    }
}
