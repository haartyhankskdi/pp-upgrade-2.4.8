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

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order;

/**
 * Class Redirect Response for Lloydscard net payment
 */
class RedirectResponse extends AbstractAction
{
    /**
     * Handle the case when both orderId and responseHash are empty.
     */
    private function handleEmptyOrder()
    {
        $failRc = $this->getRequest()->getParam('fail_rc');
        $errorMsg = $this->lloydsPaymentErrorCallback($failRc);
        $this->messageManager->addErrorMessage($errorMsg);
        return $this->_redirect($this->_baseUrl . 'checkout/cart/');
    }

     /**
      * Redirect to the cart page.
      *
      * @return \Magento\Framework\App\ResponseInterface
      */
    private function redirectToCart()
    {
        return $this->_redirect('checkout/cart');
    }

    /**
     * Handle an exception.
     *
     * @param \Exception $e
     */
    // private function handleException(\Exception $e)
    private function handleException(\Exception $e)
    {
        $failRc = $this->getRequest()->getParam('fail_rc');
        $errorMsg = $this->lloydsPaymentErrorCallback($failRc);
        $this->messageManager->addErrorMessage($errorMsg);
    }

    /**
     * Return Error Callback
     *
     * @param String $failRc
     * Return Error Callback
     */
    public function lloydsPaymentErrorCallback($failRc)
    {
        if (!empty($failRc)) {
            $errorCodeList = [
                '32000', '50001', '50002', '50003', '50004', '50005', '50006', '50007', '50008', '50010', '50011',
                '50012', '50013', '50014', '50015', '50016', '50716', '50019', '50020', '50021', '50022', '50023',
                '50030', '50031', '50033', '50034', '50035', '50036', '50037', '50038', '50039', '50041', '50042',
                '50043', '50051', '50052', '50053', '50054', '50055', '50056', '50057', '50058', '50061', '50062',
                '50063', '50065', '50066', '50067', '50068', '50070', '50075', '50078', '50082', '50087', '50090',
                '50091', '50092', '50093', '50094', '50095', '50096', '50098', '500I1', '500I2', '500N0', '500O6',
                '500P9', '500S4', '500T6', '500T8', '500U0', '500U1', '500U2', '500U3', '500U4', '500U5', '500U6',
                '500U7', '500U8', '500V0', '500V1', '500V2', '500V3', '500V4', '500V7', '500V8', '500V9', '5001A',
                '500M1', '500M2', '500M3', '500N7', '500NB', '500NC', '500X1', '500X2', '500X3', '500X4', '5102', '5101'
            ];

            if ($failRc == '5993') {
                return __('The payment was not successful; kindly attempt it once more.');
            } elseif (in_array($failRc, $errorCodeList)) {
                return __('Declined: Your bank has declined the payment.' .
                ' Please try again or use an alternative payment method.');
            } else {
                return __('An internal error has occurred, please try again. ' .
                'If the error persists please contact the Seller.');
            }
        }
        return null;
    }

    /**
     * Execute request
     */
    public function execute()
    {
        $this->helper->addLog('Redirect Response Start');
        $this->helper->addLog($this->getRequest()->getParams(), true);

        $orderId = $this->getRequest()->getParam('order_id');
        $responseHash = $this->getRequest()->getParam('response_hash');

        $failRc = $this->getRequest()->getParam('fail_rc');

        $errorMessage = $this->lloydsPaymentErrorCallback($failRc);

        if (empty($orderId) && empty($responseHash)) {
            $this->handleEmptyOrder();
            return $this->redirectToCart();
        }

        try {
            $mode = $this->config->getConfig('payment/basic/lloyds_mode');
            $config = $this->initializeReDirectPaymentParameters($mode);

            /** @var Order $order */
            $order = $this->orderRepository->get((int) $orderId);

            if (!$this->isValidOrder($order)) {
                return $this->redirectToCart();
            }

            $transactionTime = (null !== $this->getRequest()->getParam('txndatetime')) ?
                $this->getRequest()->getParam('txndatetime') : null;
                $approvalCode = (null !== $this->getRequest()->getParam('approval_code')) ?
                $this->getRequest()->getParam('approval_code') : null;
                $chargeTotal = (null !== $this->getRequest()->getParam('chargetotal')) ?
                $this->getRequest()->getParam('chargetotal') : null;
                $currency = (null !== $this->getRequest()->getParam('currency')) ?
                $this->getRequest()->getParam('currency') : null;
                $fail_reason = (null !== $this->getRequest()->getParam('fail_reason')) ?
                $this->getRequest()->getParam('fail_reason') : null;
                $status = (null !== $this->getRequest()->getParam('status')) ?
                $this->getRequest()->getParam('status') : null;
                $response_code_3dsecure = (null !== $this->getRequest()->getParam('response_code_3dsecure')) ?
                $this->getRequest()->getParam('response_code_3dsecure') : null;

                $response_3ds_code_message = $response_code_3dsecure ?
                $this->helper->getResponseMessage($response_code_3dsecure) : 0;

                $lloydsOrderId = (null !== $this->getRequest()->getParam('oid')) ?
                $this->getRequest()->getParam('oid') : null;
                $ipgTransactionId = (null !== $this->getRequest()->getParam('ipgTransactionId')) ?
                $this->getRequest()->getParam('ipgTransactionId') : null;
                $brand = (null !== $this->getRequest()->getParam('ccbrand')) ?
                $this->getRequest()->getParam('ccbrand') : null;
                $cardnumber = (null !== $this->getRequest()->getParam('cardnumber')) ?
                substr($this->getRequest()->getParam('cardnumber'), -4) : null;

                $AVSCodeArray = $this->helper->getAVSCode($approvalCode);

                $verifyResponse = $this->helper->verifyResponse(
                    $responseHash,
                    $transactionTime,
                    $approvalCode,
                    $chargeTotal,
                    $currency,
                    $config['store_id']
                );

                $paymentModel = $this->helper->getPaymentByOrderId($order->getId());
                $transactionUpdate = $paymentModel->getTransactionUpdateResponse();

            if ($transactionUpdate != 1) {
                $paymentModel->setData('remote_status_or_code', $status);
                $paymentModel->setData('approval_code', $approvalCode);
                $paymentModel->setData('response_3ds_code_message', $response_code_3dsecure);
                $paymentModel->setData('brand', $brand);
                $paymentModel->setData('last4', $cardnumber);
                $paymentModel->setData('ipgTransactionId', $ipgTransactionId);

                $paymentModel->setData('remote_message', $approvalCode.'|'.$status.'|'.$response_3ds_code_message.'|'.$lloydsOrderId); // phpcs:ignore
                $paymentModel->setData('cardnet_order_id', $lloydsOrderId);
                $paymentModel->setData('transaction_update_response', 1);
                $paymentModel->setData('street_match', $AVSCodeArray ? $AVSCodeArray['streetMatch'] : 'N');
                $paymentModel->setData('postcode_match', $AVSCodeArray ? $AVSCodeArray['postalCodeMatch'] : 'N');
                $paymentModel->setData('cvv_match', $AVSCodeArray ? $AVSCodeArray['cvvMatch'] : 'N');
                $this->paymentsRepository->save($paymentModel);

                if ($verifyResponse && ($this->helper->startsWith($approvalCode, 'Y:') ||
                    strpos(strtolower($approvalCode), 'waiting 3dsecure') !== false) &&
                    $status === 'APPROVED'
                ) {
                    return $this->saveAfterApprovePayment($order);
                } elseif (strpos(strtolower($approvalCode), 'cancel') !== false) {
                    $paymentModel->setData('status', 3);
                    $this->paymentsRepository->save($paymentModel);

                    ($errorMessage) ? $this->messageManager->addErrorMessage((string) __($errorMessage)) : '';

                    // Payment Cancelled
                    if ($order->canCancel()) {
                        $this->helper->cancelOrder($order);
                    }

                    $this->helper->setLloydsCookie('1');
                    return $this->_redirect($this->_baseUrl . 'checkout/cart/');
                } else {
                    $paymentModel->setData('status', 4);
                    $this->paymentsRepository->save($paymentModel);

                    ($errorMessage) ? $this->messageManager->addErrorMessage((string) __($errorMessage)) : '';
                    if ($order->canCancel()) {
                        $this->helper->cancelOrder($order);
                    }

                    $this->helper->setLloydsCookie('1');
                    return $this->_redirect($this->_baseUrl . 'checkout/cart/');
                }
            } else {
                $this->messageManager->addErrorMessage((string) __($errorMessage));
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        $this->helper->setLloydsCookie('1');
        return $this->redirectToCart();
    }

    /**
     * Check if the order is valid.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function isValidOrder($order)
    {
        return $order && $order->getId();
    }

    /**
     * Save After Approve Payment
     *
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function saveAfterApprovePayment($order)
    {
        $paymentModel = $this->helper->getPaymentByOrderId($order->getId());
        $redirectEmailSent = $paymentModel->getRedirectEmailSent();
        try {
            $order = $this->helper->processOrder($order, $redirectEmailSent);
            $this->savePaymentToken($this->getRequest()->getParams(), $order);
            $paymentModel->setData('status', 2);
            $this->paymentsRepository->save($paymentModel);
            $this->messageManager->addSuccessMessage((string) __('Your order number with '.$order->getIncrementId().' is successful')); // phpcs:ignore
        } catch (\Exception $ex) {
            $this->helper->addLog('Re Direct Response Exception: ' . $ex->getMessage());
            $this->helper->restoreQuote();
            $this->messageManager->addErrorMessage((string)__('Something went wrong'));
        }

        /** last successful quote */
        $this->checkoutSession->setLastQuoteId($order->getQuoteId())->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastOrderId($order->getId())
        ->setLastRealOrderId($order->getIncrementId())
        ->setLastOrderStatus($order->getStatus());

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($this->_url->getUrl('checkout/onepage/success'));
        return $resultRedirect;
    }
    
    /**
     * Save Payment Token
     *
     * @param array $responseData
     * @param \Magento\Sales\Model\Order $order
     */
    private function savePaymentToken($responseData, $order)
    {
        $payment = $order->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();
        $savecard = isset($additionalInfo['save_card']) ?
        $additionalInfo['save_card'] : false;
       
        if (!$order->getCustomerId() ||
            !isset($responseData['hosteddataid']) ||
            $savecard == false
        ) {
            return;
        }

        try {
            $paymentTokenData = [
                'customer_id' => $order->getCustomerId(),
                'payment_token' => $responseData['hosteddataid'],
                'masked' => "XXXXXXXXXXXX" . $responseData['cardLastFourDigits'],
                'brand' => $responseData['ccbrand'],
                'last4' => $responseData['cardLastFourDigits'],
                'exp_month' => str_pad($responseData['expmonth'], 2, '0', STR_PAD_LEFT),
                'exp_year' => $responseData['expyear'],
                'is_fiserv' => 0
            ];
            
            if (isset($responseData['schemeTransactionId'])) {
                $paymentTokenData['scheme_transaction_id'] = $responseData['schemeTransactionId'];
            }

            $this->helper->savePaymentToken($paymentTokenData);
            $this->helper->addLog('Token saved for customer: ' . $order->getCustomerId());
            
        } catch (\Exception $e) {
            $this->helper->addLog('Token save error: ' . $e->getMessage());
        }
    }
}
