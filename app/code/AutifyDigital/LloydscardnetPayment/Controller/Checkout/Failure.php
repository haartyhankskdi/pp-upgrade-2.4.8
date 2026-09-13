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

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Message\ManagerInterface;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as PaymentsFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Failure implements HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var PaymentsFactory
     */
    protected $paymentsFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * @var PaymentsCollectionFactory
     */
    protected $paymentsCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Constructor
     *
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param ResultFactory $resultFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     * @param Data $helper
     * @param Json $jsonSerializer
     * @param ManagerInterface $messageManager
     * @param PaymentsFactory $paymentsFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        ResultFactory $resultFactory,
        ScopeConfigInterface $scopeConfig,
        Config $config,
        Data $helper,
        Json $jsonSerializer,
        ManagerInterface $messageManager,
        PaymentsFactory $paymentsFactory,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        DateTime $dateTime
    ) {
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $resultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
        $this->messageManager = $messageManager;
        $this->paymentsFactory = $paymentsFactory;
        $this->orderRepository = $orderRepository;
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dateTime = $dateTime;
        $this->_request = $context->getRequest();
    }

    /**
     * Process failed payment response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $this->helper->addlog('Failure callback received with params: ');
        $this->helper->addLog($data, true);

        try {
            $checkoutId = $this->getRequest()->getParam('checkoutId');

            if (!$checkoutId) {
                $checkoutParams = ['checkout_id', 'id', 'reference', 'ref'];
                foreach ($checkoutParams as $param) {
                    $value = $this->getRequest()->getParam($param);
                    if ($value) {
                        $this->helper->addlog('Found checkout ID in alternative parameter: ' . $param . ' = ' . $value);
                        $checkoutId = $value;
                        break;
                    }
                }
            }

            $this->helper->addlog('Failure callback received. Checkout ID: ' . ($checkoutId ?: 'Not found'));

            if (!$checkoutId) {
                $orderId = $this->checkoutSession->getLastRealOrder()->getIncrementId();
                $this->helper->addlog('No checkout ID found in params, ' .
                'using last order ID from session: ' . ($orderId ?: 'None'));

                if (!$orderId) {
                    throw new LocalizedException(__("No order information available"));
                }

                $order = $this->orderFactory->create()->loadByIncrementId($orderId);

                // Try to get the checkout ID from the order if it was saved during redirect
                $checkoutId = $order->getData('fiserv_checkout_id');
                $this->helper->addlog('Retrieved checkout ID from order: ' . ($checkoutId ?: 'Not found'));
            } else {
                $order = $this->loadOrderByCheckoutId($checkoutId);

                // If order not found by checkout ID, fall back to last order
                if (!$order || !$order->getId()) {
                    $this->helper->addlog('Order not found by checkout ID, falling back to session order');
                    $lastOrder = $this->checkoutSession->getLastRealOrder();
                    if ($lastOrder->getIncrementId()) {
                        $order = $lastOrder;
                    }
                }
            }

            if (!$order || !$order->getId()) {
                throw new LocalizedException(__("Order not found"));
            }

            $this->helper->addlog('Order found: ' . $order->getIncrementId());

            $additionalInfo = [];
            $transactionStatus = null;
            $transactionResult = null;

            if ($checkoutId) {
                $paymentMode = $this->config->getConfig('payment/lbopcheckoutsolution/lloyds_mode');
                $transactionDetails = $this->getTransactionDetails($checkoutId, $paymentMode);

                if (!empty($transactionDetails)) {
                    $additionalInfo = $transactionDetails;
                    $this->helper->addlog($additionalInfo, true);

                    if (isset($transactionDetails['transactionStatus'])) {
                        $transactionStatus = $transactionDetails['transactionStatus'];
                    }

                    if (isset($transactionDetails['ipgTransactionDetails']) &&
                        isset($transactionDetails['ipgTransactionDetails']['transactionResult'])
                    ) {
                        $transactionResult = $transactionDetails['ipgTransactionDetails']['transactionResult'];
                    }

                    $this->helper->addlog('Transaction status: ' . ($transactionStatus ?: 'Not available'));
                    $this->helper->addlog('Transaction result: ' . ($transactionResult ?: 'Not available'));

                    $this->updatePaymentRecordToFailed($order, $transactionDetails, $data);
                }
            }

            $failureReason = 'Payment failed';

            if (isset($additionalInfo['ipgTransactionDetails']) &&
                isset($additionalInfo['ipgTransactionDetails']['ipgTransactionId'])
            ) {
                $failureReason .= ' (Transaction ID: ' .
                $additionalInfo['ipgTransactionDetails']['ipgTransactionId'] . ')';
            }

            $this->cancelOrder($order, $failureReason, $additionalInfo);

            $this->messageManager->addErrorMessage(
                __('Your payment was not successful. Please try again or select another payment method.')->render()
            );

            $this->checkoutSession->restoreQuote();
            $this->helper->addlog('Quote restored to cart');

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout/cart');
        } catch (\Exception $e) {
            $this->helper->addlog('Error in failure controller: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            $this->messageManager->addErrorMessage(
                __('There was an error processing your payment. Please try again.')->render()
            );

            try {
                $this->checkoutSession->restoreQuote();
                $this->helper->addlog('Quote restored to cart after exception');
            } catch (\Exception $restoreException) {
                $this->helper->addlog('Failed to restore quote: ' . $restoreException->getMessage());
            }

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout/cart');
        }
    }

    /**
     * Update payment record to failed status
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $transactionDetails
     * @param array $data
     * @return void
     */
    private function updatePaymentRecordToFailed($order, $transactionDetails, $data)
    {
        try {
            $collection = $this->paymentsCollectionFactory->create()
                ->addFieldToFilter('order_id', (string)$order->getId())
                ->addFieldToFilter('status', '1')
                ->setPageSize(1);

            if ($collection->getSize() > 0) {
                $paymentRecord = $collection->getFirstItem();
                
                $response = $transactionDetails['ipgTransactionDetails'] ?? [];
                $approvalCode = $response['approvalCode'] ?? '';
                $transactionStatus = $response['transactionStatus'] ?? '';
                $ipgTransactionId = $response['ipgTransactionId'] ?? '';
                $response3dsCodeMessage = $response['processor']['responseMessage'] ?? '';
                
                $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $ipgTransactionId;

                $paymentRecord->setData('response_3ds_code_message', $data['message']);
                $paymentRecord->setData('remote_status_or_code', $transactionStatus);
                $paymentRecord->setData('approval_code', $approvalCode);
                $paymentRecord->setData('remote_message', $transactionMessage);
                $paymentRecord->setData('status', 4);
                $paymentRecord->setData('updated_at', $this->dateTime->date('Y-m-d H:i:s'));
                
                $this->paymentsRepository->save($paymentRecord);
                
                $this->helper->addLog('Payment record updated to failed status for order: ' . $order->getIncrementId());
            }
        } catch (\Exception $e) {
            $this->helper->addLog('Error updating payment record to failed: ' . $e->getMessage());
        }
    }

    /**
     * Load order by checkout ID
     *
     * @param string $checkoutId
     * @return \Magento\Sales\Model\Order|null
     */
    private function loadOrderByCheckoutId($checkoutId): ?Order
    {
        $this->helper->addlog('Attempting to load order by checkout ID: ' . $checkoutId);

        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('fiserv_checkout_id', $checkoutId)
            ->setPageSize(1);

        $this->helper->addlog('Order collection size: ' . $orderCollection->getSize());

        if ($orderCollection->getSize()) {
            /** @var Order $order */
            $order = $orderCollection->getFirstItem();
            $this->helper->addlog('Order found: ' . $order->getIncrementId());
            return $order;
        }

        $this->helper->addlog('Trying to find order by orderId in transaction details');
        $allOrders = $this->orderCollectionFactory->create()
            ->setPageSize(10)
            ->setOrder('created_at', 'DESC');

        foreach ($allOrders as $potentialOrder) {
            $payment = $potentialOrder->getPayment();
            if ($payment) {
                $additionalInfo = $payment->getAdditionalInformation();
                if (isset($additionalInfo['checkout_id']) && $additionalInfo['checkout_id'] === $checkoutId) {
                    $this->helper->addlog('Order found via payment additional info: ' .
                    $potentialOrder->getIncrementId());
                    return $potentialOrder;
                }
            }
        }

        $this->helper->addlog('No order found for checkout ID: ' . $checkoutId);
        return null;
    }

    /**
     * Get transaction details from payment gateway
     *
     * @param string $checkoutId
     * @param string $paymentMode
     * @return array
     */
    private function getTransactionDetails($checkoutId, $paymentMode)
    {
        $this->helper->addlog('Fetching transaction details for checkout ID: ' . $checkoutId);

        try {
            $response = $this->helper->callFiservCurl(Data::FISERV_API_URL . "/{$checkoutId}", [], 'GET');
            $this->helper->addlog($response, true);

            if (isset($response['status']) && $response['status'] === 'success' && isset($response['response'])) {
                $this->helper->addlog('Transaction details retrieved successfully');

                if (is_object($response['response'])) {
                    return $this->convertObjectToArray($response['response']);
                }

                return (array)$response['response'];
            }

            $this->helper->addlog('Failed to retrieve transaction details');
            return [];
        } catch (\Exception $e) {
            $this->helper->addlog('Error fetching transaction details: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Convert stdClass objects to arrays recursively
     *
     * @param mixed $object
     * @return mixed
     */
    private function convertObjectToArray($object)
    {
        if (is_object($object)) {
            $object = (array) $object;
        }

        if (is_array($object)) {
            $result = [];
            foreach ($object as $key => $value) {
                $result[$key] = $this->convertObjectToArray($value);
            }
            return $result;
        }

        return $object;
    }

    /**
     * Cancel order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $reason
     * @param array $additionalInfo
     * @return void
     */
    private function cancelOrder($order, $reason, $additionalInfo = [])
    {
        try {
            $this->helper->addlog('Attempting to cancel order: ' . $order->getIncrementId());

            if (!empty($additionalInfo)) {
                $payment = $order->getPayment();

                if (isset($additionalInfo['ipgTransactionDetails']) &&
                    isset($additionalInfo['ipgTransactionDetails']['ipgTransactionId'])
                ) {
                    $payment->setLastTransId($additionalInfo['ipgTransactionDetails']['ipgTransactionId']);
                    $this->helper->addlog('Transaction ID saved: ' .
                    $additionalInfo['ipgTransactionDetails']['ipgTransactionId']);
                }

                // Proper way to set additional information in Magento 2
                $currentAdditionalInfo = $payment->getAdditionalInformation();
                
                if (isset($additionalInfo['checkoutId'])) {
                    $currentAdditionalInfo['checkout_id'] = $additionalInfo['checkoutId'];
                    $this->helper->addlog('Checkout ID saved to payment: ' . $additionalInfo['checkoutId']);
                }

                if (isset($additionalInfo['transactionStatus'])) {
                    $currentAdditionalInfo['transaction_status'] = $additionalInfo['transactionStatus'];
                    $this->helper->addlog('Transaction status saved: ' . $additionalInfo['transactionStatus']);
                }

                if (isset($additionalInfo['paymentMethodUsed']) &&
                    isset($additionalInfo['paymentMethodUsed']['cards']) &&
                    isset($additionalInfo['paymentMethodUsed']['cards']['cardNumber'])
                ) {
                    $currentAdditionalInfo['card_number'] = $additionalInfo['paymentMethodUsed']['cards']['cardNumber'];
                    $this->helper->addlog('Card number saved: ' .
                    $additionalInfo['paymentMethodUsed']['cards']['cardNumber']);
                }

                if (isset($additionalInfo['ipgTransactionDetails']) &&
                    isset($additionalInfo['ipgTransactionDetails']['approvalCode'])
                ) {
                    $currentAdditionalInfo['approval_code'] = $additionalInfo['ipgTransactionDetails']['approvalCode'];
                    $this->helper->addlog('Approval code saved: ' .
                    $additionalInfo['ipgTransactionDetails']['approvalCode']);
                }

                $currentAdditionalInfo['transaction_details'] = $this->jsonSerializer->serialize($additionalInfo);

                $payment->setAdditionalInformation($currentAdditionalInfo);

                // Save the order to persist payment changes
                $this->orderRepository->save($order);
                $this->helper->addlog('Additional information saved to payment');
            }

            if ($order->canCancel()) {
                $order->cancel();
                $order->addCommentToStatusHistory($reason);
                $this->orderRepository->save($order);

                $this->helper->addlog('Order ' . $order->getIncrementId() . ' canceled: ' . $reason);
            } else {
                $order->addCommentToStatusHistory(
                    'Payment failed but order could not be automatically canceled. Reason: ' . $reason .
                    ' Current state: ' . $order->getState()
                );
                $this->orderRepository->save($order);

                $this->helper->addlog('Order ' . $order->getIncrementId() .
                ' cannot be canceled. Current state: ' . $order->getState());
            }
        } catch (\Exception $e) {
            $this->helper->addlog('Error canceling order: ' .
            $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * Retrieve request object
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    public function getRequest()
    {
        return $this->_request;
    }
}
