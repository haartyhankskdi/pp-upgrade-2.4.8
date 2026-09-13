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
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Session\SessionManagerInterface;

class MotoResponseUrl extends AbstractAction implements CsrfAwareActionInterface
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @param Context $context
     * @param \AutifyDigital\LloydscardnetPayment\Helper\Data $helperData
     * @param \AutifyDigital\LloydscardnetPayment\Model\Config $config
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs $lcnetPaymentjs
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Api\Search\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ResultFactory $resultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        Context $context,
        \AutifyDigital\LloydscardnetPayment\Helper\Data $helperData,
        \AutifyDigital\LloydscardnetPayment\Model\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs $lcnetPaymentjs,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Api\Search\SearchCriteriaBuilder $searchCriteriaBuilder,
        ResultFactory $resultFactory,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        PageFactory $resultPageFactory,
        RedirectFactory $resultRedirectFactory,
        SessionManagerInterface $coreSession
    ) {
        parent::__construct(
            $context,
            $helperData,
            $config,
            $checkoutSession,
            $resultJsonFactory,
            $resultLayoutFactory,
            $orderFactory,
            $lcPaymentsFactory,
            $quoteRepository,
            $serializer,
            $paymentTokenFactory,
            $paymentTokenRepository,
            $storeManager,
            $lcnetPaymentjs,
            $encryptor,
            $dateTime,
            $searchCriteriaBuilder,
            $resultFactory,
            $orderRepository,
            $paymentsRepository,
            $paymentsCollectionFactory,
            $orderCollectionFactory,
            $coreSession
        );
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * Create csrf validation exception
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Validate for csrf
     *
     * @param RequestInterface $request
     * @return bool
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helper->addLog('MOTO Response URL Start');
        
        try {
            $params = $this->getRequest()->getParams();
            $this->helper->addLog($params, true);
            $orderId = $params['order_id'] ?? '';
            $storeId = null;
            if (!empty($orderId)) {
                try {
                    $storeId = $this->orderRepository->get((int) $orderId)->getStoreId();
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $storeId = null;
                }
            }
            $mode = $this->config->getConfig('payment/basic/lloyds_mode', $storeId);
            $config = $this->config->getBasicConfigurations($mode, $storeId);

            $approvalCode = $params['approval_code'] ?? '';
            $ipgTransactionId = $params['ipgTransactionId'] ?? '';
            $status = $params['status'] ?? '';
            $fail_rc = $params['fail_rc'] ?? '';
            $oid = $params['oid'] ?? '';
            $params['storename'] =  $config['store_id'] ?? '';
            $ccbrand = $params['ccbrand'] ?? '';
            $cardnumber = $params['cardnumber'] ?? '';
            $last4 = substr($cardnumber, -4);
            
            if ($this->isGatewayCallback($params, $storeId)) {
                $this->helper->addLog('Valid gateway callback detected for order #' . $orderId);
                
                $this->processBasicResponse($orderId, $status, $approvalCode, $ipgTransactionId, $oid, $ccbrand, $last4);
                
                $resultPage = $this->resultPageFactory->create();
                $resultPage->getConfig()->getTitle()->set((string) __('Payment Response'));
                $resultPage->getConfig()->setPageLayout('empty');
                $block = $resultPage->getLayout()->getBlock('cardnet_moto_response');

                /** @var \Magento\Framework\View\Element\Template $block */
                $block->setData('order_id', $orderId);
                $block->setData('status', $status);
                $block->setData('approval_code', $approvalCode);
                $block->setData('fail_rc', $fail_rc);
                
                return $resultPage;
            } else {
                $this->helper->addLog('Invalid request detected - missing required parameters or validation failed');
                throw new LocalizedException(__('Invalid request. Access denied.'));
            }
            
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->helper->addLog('MOTO Response Error: ' . $e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage((string) __(
                'An unexpected error occurred. Please try again later.'
            ));
            $this->helper->addLog('MOTO Response Exception: ' . $e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
    }
    
    /**
     * Gateway callback
     *
     * @param array $params
     * @param int|string|null $storeId
     * @return bool
     */
    private function isGatewayCallback($params, $storeId = null)
    {
        $requiredParams = [
            'order_id',
            'response_hash',
            'txntype',
            'oid',
            'txndatetime',
            'approval_code',
            'chargetotal',
            'currency'
        ];
        
        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                $this->helper->addLog('Missing required parameter: ' . $param);
                return false;
            }
        }
        
        $orderId = $params['order_id'];
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get((int) $orderId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->helper->addLog('Order not found: ' . $orderId);
            return false;
        }
        if (!$order->getEntityId()) {
            $this->helper->addLog('Order not found: ' . $orderId);
            return false;
        }
        
        if (isset($params['response_hash'])) {
            $isValidHash = $this->verifyResponseHash($params, $storeId);
            if (!$isValidHash) {
                $this->helper->addLog(
                    'Hash verification failed for order #' . $orderId . ' - denying request'
                );

                return false;
            }
        }
        
        if (!empty($params['approval_code']) &&
            !empty($params['txntype']) && !empty($params['status'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verify the response hash from the gateway
     *
     * @param array $params
     * @param int|string|null $storeId
     * @return bool
     */
    private function verifyResponseHash($params, $storeId = null)
    {
        try {
            $responseHash = $params['response_hash'] ?? '';
            $tdate = $params['txndatetime'] ?? '';
            $approvalCode = $params['approval_code'] ?? '';
            $chargeTotal = $params['chargetotal'] ?? '';
            $currency = $params['currency'] ?? '';
            $storeName = $params['storename'] ?? '';
            
            $isValid = $this->helper->verifyResponse(
                $responseHash,
                $tdate,
                $approvalCode,
                $chargeTotal,
                $currency,
                $storeName,
                $storeId
            );
            
            return $isValid;
        } catch (\Exception $e) {
            $this->helper->addLog('Hash verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process basic payment response for UI status
     *
     * @param string $orderId
     * @param string $status
     * @param string $approvalCode
     * @param string $ipgTransactionId
     * @param string $oid
     * @return void
     */
    private function processBasicResponse(
        $orderId,
        $status,
        $approvalCode,
        $ipgTransactionId,
        $oid,
        $ccbrand,
        $cardnumber
    ) {
        if (empty($orderId)) {
            return;
        }
        
        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get((int) $orderId);
            if (!$order->getEntityId()) {
                return;
            }
            $paymentModel = $this->helper->getPaymentByOrderId($orderId);
            $this->helper->addLog('MOTO Basic Response: Order #' . $orderId . ', Status: ' . $status);
            
            if ($status === 'APPROVED' || $this->helper->startsWith($approvalCode, 'Y:')) {
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $order->getPayment();
                $payment->setTransactionId($approvalCode);
                $payment->setLastTransId($approvalCode);
                $payment->setAdditionalInformation('approval_code', $approvalCode);
                $payment->setAdditionalInformation('transaction_status', $status);

                $paymentModel->setData('remote_status_or_code', $status);
                $paymentModel->setData('approval_code', $approvalCode);
                $paymentModel->setData('ipgTransactionId', $ipgTransactionId);
                $paymentModel->setData('brand', $ccbrand);
                $paymentModel->setData('last4', $cardnumber);
                $paymentModel->setData('remote_message', $approvalCode.'|'.$status .'|'. $oid); // phpcs:ignore
                $paymentModel->setData('cardnet_order_id', $oid);
                $paymentModel->setData('transaction_update_response', 1);
                $paymentModel->setData('status', 2);
                $this->paymentsRepository->save($paymentModel);

                if ($order->canInvoice()) {
                    $this->helper->addLog('Creating invoice for order #' . $orderId);
                    $this->helper->generateInvoice($order);
                }

                $this->orderRepository->save($order);
                $this->helper->addLog('Order #' . $orderId . ' successfully updated to processing status');
            } else {
                $paymentModel->setData('remote_status_or_code', $status);
                $paymentModel->setData('approval_code', $approvalCode);
                $paymentModel->setData('ipgTransactionId', $ipgTransactionId);

                $paymentModel->setData('remote_message', $approvalCode.'|'.$status .'|'. $oid); // phpcs:ignore
                $paymentModel->setData('cardnet_order_id', $oid);
                $paymentModel->setData('brand', $ccbrand);
                $paymentModel->setData('last4', $cardnumber);
                $paymentModel->setData('transaction_update_response', 1);
                $paymentModel->setData('status', 4);
                $this->paymentsRepository->save($paymentModel);
                $this->helper->addLog('Payment not approved for order #' . $orderId . '. Status: ' . $status);

                if ($order->canCancel()) {
                    $this->helper->cancelOrder($order);
                    $this->helper->addLog(
                        'Order #' . $orderId . ' canceled due to failed MOTO payment. Status: ' . $status
                    );
                } else {
                    $this->helper->addLog(
                        'Order #' . $orderId . ' not cancellable (state: ' . $order->getState() . '); skipping cancel'
                    );
                }
            }
            
        } catch (\Exception $e) {
            $this->helper->addLog('MOTO Response Exception: ' . $e->getMessage());
        }
    }
}
