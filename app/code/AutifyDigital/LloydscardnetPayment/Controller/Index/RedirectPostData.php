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

class RedirectPostData extends AbstractAction
{
    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory
     */
    protected $lcPaymentsFactory;
    
    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helper->addLog('RedirectPostData Controller call');
        $result = $this->resultJsonFactory->create();

        $tokenIdParam = $this->getRequest()->getParam('token_id');
        $tokenId = $tokenIdParam ? $this->helper->getTokenValue($this->encryptor->decrypt($tokenIdParam)) : null;
        $saveCard = (bool)$this->getRequest()->getParam('save_card', false);

        try {
            $mode = $this->config->getConfig('payment/basic/lloyds_mode');
            $config = $this->initializeReDirectPaymentParameters($mode);
        } catch (\Exception $e) {
            $this->helper->restoreQuote();
            $this->helper->addLog('Redirect Order Exception1: ' . $e->getMessage());
            $this->messageManager->addErrorMessage((string) __($e->getMessage()));
            $postData = [
                'action' => '',
                'error' => true,
                'fields' => []
            ];
            return $result->setData($postData);
        }

        $currentOrder = $this->getCurrentOrder();

        if (!$currentOrder || !$currentOrder->getIncrementId()) {
            $this->helper->addLog('Order Id not found');
            $this->messageManager->addWarningMessage((string) __("Invalid payment request!"));
            $postData = [
                'action' => '',
                'error' => true,
                'fields' => []
            ];
            return $result->setData($postData);
        }

        $this->helper->addLog('Order Id : ' . $currentOrder->getId());
        try {
            $action = $config['processing_url'];
            $amount = (float)$currentOrder->getGrandTotal();
            if ($amount <= 0) {
                $amount = (float)$currentOrder->getBaseGrandTotal();
            }
            $ccy_code = $currentOrder->getOrderCurrencyCode();
            $transactionCode = $currentOrder->getIncrementId() . '-' . time();

            $currentOrder->setStatus("pending_payment");
            $this->orderRepository->save($currentOrder);
            $newTxCode = $this->helper->generateMerchantTransactionId();

            $currency = $this->helper->getIsoCurrencyCodeFromCurrencyCode($ccy_code);

            $billingAddress = $currentOrder->getBillingAddress();
            $shippingAddress = $currentOrder->getShippingAddress();

            // If cart is virtual
            if (!$shippingAddress || $shippingAddress->getFirstname() == null) {
                $shippingAddress = $currentOrder->getBillingAddress();
            }

            $timeZone = $this->helper->timezone();
            $dt = $timeZone->date();

            //YYYY:MM:DD-hh:mm:ss
            $transactionTime = $dt->format('Y:m:d-H:i:s');

            $billName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
            $shipName = $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();

            $email = $billingAddress->getEmail();
            if (!$email) {
                $email = $currentOrder->getCustomerEmail();
            }

            $chargeTotal = number_format((float)$amount, 2, '.', '');
            $hashValue = $this->helper->createHash(
                $config['store_id'],
                $transactionTime,
                $chargeTotal,
                $currency
            );

            $payment = $currentOrder->getPayment();
            $methodCode = $payment->getMethod();

            if ($payment) {
                $additionalInfo = $payment->getAdditionalInformation() ?: [];
                $additionalInfo['save_card'] = $saveCard;
                
                if ($tokenId) {
                    $additionalInfo['token_id'] = $tokenId;
                }
                
                $payment->setAdditionalInformation($additionalInfo);

                $this->orderRepository->save($currentOrder);
            }

            $paymentMethod = $this->config->getConfig('payment/lcnetredirect/allowed_payment_methods');
            
            if ($methodCode == 'cardnetgooglepay') {
                $paymentMethod = 'googlepay';
            } elseif ($methodCode == 'cardnetapplepay') {
                $paymentMethod = 'applepay';
            } elseif ($paymentMethod != null) {
                $paymentMethod = $paymentMethod;
            } else {
                $paymentMethod = '';
            }

            if ($this->helper->getGooglepayPaymentMethodInteration() == 'hosted' &&
                $methodCode == 'cardnetgooglepay'
            ) {
                $paymentMethod = 'googlepay';
            } elseif ($this->helper->getApplepayPaymentMethodInteration() == 'hosted' &&
                $methodCode == 'cardnetapplepay'
            ) {
                $paymentMethod = 'applepay';
            }

            $challengeIndicator = $this->config->getConfig('payment/basic/challenge_indicator');
            $enablePONumber = $this->config->getConfig('payment/basic/active_po_number');
            $configPONumber = $this->config->getConfig('payment/basic/dynamic_data_po_number');
            
            $configponumberarray = explode("|", $configPONumber);
            $ponumber = '';
            if ($configponumberarray[0] !== '' && $enablePONumber == 1) {
                $keys = array_keys($configponumberarray);
                $lastKey = end($keys);

                foreach ($configponumberarray as $key => $configponumberval) {
                    if ($key == $lastKey) {
                        $ponumber .= $currentOrder->getData($configponumberval);
                    } else {
                        if ($currentOrder->getData($configponumberval)) {
                            $ponumber .= $currentOrder->getData($configponumberval) . "_";
                        }
                    }
                }
                if ($ponumber) {
                    $ponumber = substr($ponumber, 0, 49);
                }
            }
            if (!$this->helper->startsWith((string)$config['store_id'], '22')) {
                $this->helper->addLog('RedirectPostData: Invalid store ID - ' . $config['store_id']);
                $this->helper->restoreQuote();
                $this->messageManager->addErrorMessage((string) __('Invalid store configuration. Please contact support.'));
                
                $postData = [
                    'action' => '',
                    'error' => true,
                    'fields' => ['Store ID is invalid.']
                ];
                
                return $result->setData($postData);
            }
            
            $formData = [
                'txntype' => 'sale',
                'timezone' => date_default_timezone_get(),
                'txndatetime' => $transactionTime,
                'paymentMethod' => $paymentMethod,
                'hash_algorithm' => 'SHA256',
                'hash' => $hashValue,
                'oid'  => $this->helper->getOrderIdWithSuffix($currentOrder->getIncrementId()),
                'storename' => $config['store_id'],
                'mode' => $config['pay_mode'],
                'checkoutoption' => $config['page_option'],
                'bcompany' => $billingAddress->getCompany(),
                'bname' => $billName,
                'baddr1' => implode(' ', $billingAddress->getStreet()),
                'baddr2' => '',
                'bcity' => $billingAddress->getCity(),
                'bstate' => $billingAddress->getRegion(),
                'bcountry' => $billingAddress->getCountryId(),
                'bzip' => $billingAddress->getPostcode(),
                'phone' => $billingAddress->getTelephone(),
                'email' => $email,
                'sname' => $shipName,
                'saddr1' => implode(' ', $shippingAddress->getStreet()),
                'saddr2' => '',
                'scity' => $shippingAddress->getCity(),
                'sstate' => $shippingAddress->getRegion(),
                'scountry' => $shippingAddress->getCountryId(),
                'szip' => $shippingAddress->getPostcode(),
                'comments' => 'AutifyDigital LBOP Magento HPP',
                'purchaseOrderNumber' => $ponumber,
                'threeDSRequestorChallengeIndicator' => $challengeIndicator,
                'chargetotal' => $chargeTotal,
                'currency' => $currency,
                'merchantTransactionId' => $newTxCode,
                'responseFailURL' => $config['return_url'],
                'responseSuccessURL' => $config['return_url'],
                'transactionNotificationURL' => $config['transaction_notification_url'],
                'authenticateTransaction' => 'true'
            ];

            if ($tokenId) {
                // Using existing saved card token
                $formData['hosteddataid'] = $tokenId;
                $this->helper->addLog('Using existing token: ' . $tokenId);
            } else {
                // Handle new card - if customer wants to save it
                if ($saveCard && $currentOrder->getCustomerId()) {
                    $formData['assignToken'] = 'true';
                    $formData['tokenType'] = 'MULTIPAY';
                    $this->helper->addLog('Creating new MULTIPAY token for customer: ' . $currentOrder->getCustomerId());
                }
            }

            if ($this->config->getConfig('payment/lcnetredirect/dynamic_merchant_name')) {
                $formData['dynamicMerchantName'] =
                $this->config->getConfig('payment/lcnetredirect/dynamic_merchant_name');
            }

            $this->helper->addLog($formData, true);

            $postData = [
                'action' => $action,
                'error' => false,
                'fields' => $formData
            ];

            $lcPaymentModel = $this->lcPaymentsFactory->create();
            $lcPaymentModel->setData('payment_method', 'lcnetredirect');
            $lcPaymentModel->setData('amount', $chargeTotal);
            $lcPaymentModel->setData('status', 1);
            $lcPaymentModel->setData('order_id', $currentOrder->getId());
            $lcPaymentModel->setData('order_increment_id', $currentOrder->getIncrementId());
            $lcPaymentModel->setData('remote_reference', $newTxCode);

            if ($tokenId) {
                $lcPaymentModel->setData('save_card', 1);
                $lcPaymentModel->setData('remote_message', 'Used saved card token: ' . $tokenId);
            }
            if ($saveCard && !$tokenId && $currentOrder->getCustomerId()) {
                $lcPaymentModel->setData('save_card', 1);
                $lcPaymentModel->setData('remote_message', 'New card - save requested for customer: ' . $currentOrder->getCustomerId());
            }
            $this->paymentsRepository->save($lcPaymentModel);

            return $result->setData($postData);
        } catch (\Exception $e) {
            $this->helper->restoreQuote();
            $this->helper->addLog('Redirect Order Exception2: ' . $e->getMessage());

            $this->messageManager->addErrorMessage((string) __('Something went wrong'));
            $postData = [
                'action' => '',
                'error' => true,
                'fields' => []
            ];
        }

        return $result->setData($postData);
    }
}
