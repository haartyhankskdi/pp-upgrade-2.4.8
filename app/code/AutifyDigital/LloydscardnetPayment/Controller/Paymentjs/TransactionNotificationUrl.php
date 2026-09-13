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

use AutifyDigital\LloydscardnetPayment\Helper\Data as ApiHelper;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;

class TransactionNotificationUrl extends \AutifyDigital\LloydscardnetPayment\Controller\Index\AbstractAction
{
    /**
     * @var PaymentTokenCollectionFactory
     */
    protected $paymentTokenCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Lcnetpaymentjs
     */
    protected $lcnetPaymentjs;

    /**
     * @var ApiHelper
     */
    protected $helper;

    /**
     * @var string
     */
    protected $formKey;

    /**
     * @var string
     */
    protected $_baseUrl;

    /**
     * @var string
     */
    protected $_cancel_url;

    /**
     * @var string
     */
    protected $_return_url;

    /**
     * @var string
     */
    protected $mediaUrl;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var LcPaymentsFactory
     */
    protected $lcPaymentsFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PaymentTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;

    /**
     * TransactionNotificationUrl constructor
     *
     * @param Context $context
     * @param ApiHelper $helperData
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param OrderFactory $orderFactory
     * @param LcPaymentsFactory $lcPaymentsFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param SerializerInterface $serializer
     * @param PaymentTokenFactory $paymentTokenFactory
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param StoreManagerInterface $storeManager
     * @param Lcnetpaymentjs $lcnetPaymentjs
     * @param EncryptorInterface $encryptor
     * @param DateTime $dateTime
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderSender $orderSender
     * @param PaymentTokenCollectionFactory $paymentTokenCollectionFactory
     * @param ManagerInterface $messageManager
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        Context $context,
        ApiHelper $helperData,
        Config $config,
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory,
        LayoutFactory $resultLayoutFactory,
        OrderFactory $orderFactory,
        LcPaymentsFactory $lcPaymentsFactory,
        CartRepositoryInterface $quoteRepository,
        SerializerInterface $serializer,
        PaymentTokenFactory $paymentTokenFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        StoreManagerInterface $storeManager,
        Lcnetpaymentjs $lcnetPaymentjs,
        EncryptorInterface $encryptor,
        DateTime $dateTime,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        OrderSender $orderSender,
        PaymentTokenCollectionFactory $paymentTokenCollectionFactory,
        ManagerInterface $messageManager,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
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

        $this->orderSender = $orderSender;
        $this->paymentTokenCollectionFactory = $paymentTokenCollectionFactory;
        $this->messageManager = $messageManager;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    /**
     * Execute action - handles AJAX polling for 3DS authentication status
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $isAjaxCheck = $this->getRequest()->getParam('ajax_check');

        $this->helper->addLog('PaymentJS TransactionNotificationUrl Start');
        $this->helper->addLog($this->getRequest()->getParams(), true);

        if ($isAjaxCheck) {
            return $this->handleAjaxStatusCheck();
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/cart');
        return $resultRedirect;
    }

    /**
     * Handle payment error - update payment status, cancel order, restore cart, set error message
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $errorMessage
     * @param \AutifyDigital\LloydscardnetPayment\Model\Payments|null $paymentModel
     * @param \stdClass|null $response
     * @return void
     */
    private function handlePaymentError($order, $errorMessage, $paymentModel = null, $response = null)
    {
        try {
            $this->helper->addLog('Handling payment error for order: ' . $order->getIncrementId());

            if ($paymentModel && $paymentModel->getId()) {
                $paymentModel->setData('status', 4);

                // Extract gateway fields from response using the standard format:
                // approvalCode|transactionStatus|3dsMessage|orderId
                $approvalCode = $response->approvalCode ?? ($paymentModel->getData('approval_code') ?? '');
                $transactionStatus = $response->transactionStatus ?? ($response->transactionResult ?? 'ERROR');
                $orderId = $response->orderId ?? ($paymentModel->getData('cardnet_order_id') ?? '');
                $responseCode3ds = isset($response->secure3dResponse->responseCode3dSecure)
                    ? $response->secure3dResponse->responseCode3dSecure : '';
                $responseMessage3ds = !empty($responseCode3ds)
                    ? $this->helper->getResponseMessage($responseCode3ds) : ($response->error->message ?? ($response->errorMessage ?? $errorMessage));

                $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $responseMessage3ds . '|' . $orderId;

                $paymentModel->setData('remote_status_or_code', $transactionStatus);
                $paymentModel->setData('approval_code', $approvalCode);
                $paymentModel->setData('remote_message', $transactionMessage);

                $this->paymentsRepository->save($paymentModel);
                $this->helper->addLog('Payment status set to failed for order: ' . $order->getIncrementId());
            }

            if ($order->getId() && $order->canCancel()) {
                $order->cancel();
                $order->addStatusHistoryComment('Order cancelled due to payment failure: ' . $errorMessage);
                $this->orderRepository->save($order);
                $this->helper->addLog('Order cancelled: ' . $order->getIncrementId());
            }

            $quote = $this->quoteRepository->get($order->getQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(true);
                $quote->setReservedOrderId(null);
                $this->quoteRepository->save($quote);
                $this->checkoutSession->replaceQuote($quote);
                $this->helper->addLog('Cart restored for quote: ' . $quote->getId());
            }

            $this->messageManager->addErrorMessage($errorMessage);

        } catch (\Exception $e) {
            $this->helper->addLog('Error handling payment failure: ' . $e->getMessage());
        }
    }

    /**
     * Handle AJAX status check for payment transaction
     *
     * @return ResultInterface
     */
    private function handleAjaxStatusCheck()
    {
        $resultJson = $this->resultJsonFactory->create();
        $currentOrder = null;

        try {
            $ipgId = $this->getRequest()->getParam('ipg_id');
            $attemptNumber = $this->getRequest()->getParam('attempt', 0);

            if (empty($ipgId)) {
                return $resultJson->setData([
                    'status' => 'error',
                    'message' => 'Invalid request',
                    'redirect_url' => $this->_url->getUrl('checkout/cart')
                ]);
            }

            $currentOrder = $this->getCurrentOrder();

            if (!$currentOrder->getId()) {
                return $resultJson->setData([
                    'status' => 'error',
                    'message' => 'Order not found',
                    'redirect_url' => $this->_url->getUrl('checkout/cart')
                ]);
            }

            $paymentModel = $this->helper->getPaymentByOrderId($currentOrder->getId());

            if (!$paymentModel || !$paymentModel->getId()) {
                return $resultJson->setData([
                    'status' => 'pending',
                    'message' => 'Payment processing...'
                ]);
            }

            $iframeReceived = (int) $paymentModel->getIframeReceived();
            $threeDSReceived = (int) $paymentModel->getThreedsReceived();
            $status = $paymentModel->getStatus();
            $transactionStatus = $paymentModel->getData('remote_status_or_code');
            $approvalCode = $paymentModel->getData('approval_code');

            $this->helper->addLog('TransactionNotificationUrl - IframeReceived: ' . $iframeReceived .
                ', ThreeDSReceived: ' . $threeDSReceived .
                ', Status: ' . $status .
                ', TransactionStatus: ' . $transactionStatus.
                ' Attempt: ' . $attemptNumber);

            if ($attemptNumber < 10 && $iframeReceived < 1) {
                return $resultJson->setData([
                    'status' => 'waiting',
                    'message' => 'Waiting for authentication...'
                ]);
            }

            if ($threeDSReceived == 0) {
                $apiPath = str_replace('{ipgTransactionId}', $ipgId, ApiHelper::PATCH_PAYMENTS_API_URL);

                $mode = $this->config->getConfig('payment/basic/lloyds_mode');
                $config = $this->initializeReDirectPaymentParameters($mode);
                $patchPayment = $this->getPayload($currentOrder, $config);

                $curlCall = $this->helper->callCurl($apiPath, $patchPayment, 'PATCH');
                $this->helper->addLog($curlCall, true);

                $paymentModel->setData('threeds_received', 1);
                $this->paymentsRepository->save($paymentModel);

                if (isset($curlCall['status']) && $curlCall['status'] != 'error') {
                    $response = $curlCall['response'];

                    if (!isset($response->transactionStatus)) {
                        // ERROR - Cancel order and restore cart
                        $this->handlePaymentError($currentOrder, 'Payment processing failed. Please try again.', $paymentModel, $response);

                        return $resultJson->setData([
                            'status' => 'error',
                            'message' => 'Payment processing failed',
                            'redirect_url' => $this->_url->getUrl('checkout/cart')
                        ]);
                    }

                    $this->updatePaymentModel($paymentModel, $response);

                    if (isset($response->authenticationResponse->params)) {
                        $termURL = $response->authenticationResponse->params->termURL ?? '';
                        $acsURL = $response->authenticationResponse->params->acsURL ?? '';
                        $cReq = $response->authenticationResponse->params->cReq ?? '';

                        if (!empty($termURL) && !empty($acsURL) && !empty($cReq)) {
                            // Submit the challenge through the same-origin ChallengeFrame
                            // controller, which dynamically whitelists the bank ACS host.
                            // The challenge host can differ from the 3DS-method host that
                            // the ThreeDSFrame page already whitelisted, so posting the ACS
                            // URL directly from that page can be blocked by the static CSP.
                            return $resultJson->setData([
                                'status' => 'challenge_required',
                                'message' => '3DS challenge required',
                                'challenge_data' => $this->helper
                                    ->buildChallengeFormData((string)$acsURL, (string)$cReq, (string)$termURL)
                            ]);
                        }
                    }

                    $status = $paymentModel->getStatus();
                    $approvalCode = $paymentModel->getData('approval_code');
                    $transactionStatus = $paymentModel->getData('remote_status_or_code');

                } else {
                    // ERROR - Cancel order and restore cart
                    $errorResponse = $curlCall['response'] ?? new \stdClass();
                    $errorMessage = $errorResponse->errorMessage
                        ?? 'Payment processing failed. Please try again.';

                    $this->handlePaymentError($currentOrder, $errorMessage, $paymentModel, $errorResponse);

                    return $resultJson->setData([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'redirect_url' => $this->_url->getUrl('checkout/cart')
                    ]);
                }
            }

            if ($this->helper->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
                return $resultJson->setData([
                    'status' => 'complete',
                    'message' => 'Payment approved',
                    'redirect_url' => $this->_url->getUrl('checkout/onepage/success')
                ]);
            }

            if ($status == 4 || in_array($transactionStatus, ['DECLINED', 'FAILED', 'VALIDATION_FAILED'])) {
                // ERROR - Cancel order and restore cart
                $errorMessage = 'Payment ' . strtolower($transactionStatus) . '. Please try again or use a different payment method.';
                $this->handlePaymentError($currentOrder, $errorMessage, $paymentModel);

                return $resultJson->setData([
                    'status' => 'failed',
                    'message' => 'Payment failed',
                    'redirect_url' => $this->_url->getUrl('checkout/cart')
                ]);
            }

            return $resultJson->setData([
                'status' => 'pending',
                'message' => 'Payment processing...'
            ]);

        } catch (\Exception $e) {
            $this->helper->addLog('AJAX Status Check Error: ' . $e->getMessage());

            // ERROR - Cancel order and restore cart
            if ($currentOrder && $currentOrder->getId()) {
                $paymentModel = $this->helper->getPaymentByOrderId($currentOrder->getId());
                $errorResponse = new \stdClass();
                $errorResponse->transactionStatus = 'ERROR';
                $errorResponse->errorMessage = $e->getMessage();
                $this->handlePaymentError(
                    $currentOrder,
                    'An error occurred during payment processing. Please try again.',
                    $paymentModel,
                    $errorResponse
                );
            }

            return $resultJson->setData([
                'status' => 'error',
                'message' => 'Something went wrong',
                'redirect_url' => $this->_url->getUrl('checkout/cart')
            ]);
        }
    }

    /**
     * Update payment model with API response data
     *
     * @param \AutifyDigital\LloydscardnetPayment\Model\Payments $paymentModel
     * @param \stdClass $response
     * @return void
     */
    private function updatePaymentModel($paymentModel, $response)
    {
        $approvalCode = $response->approvalCode ?? null;
        $transactionStatus = $response->transactionStatus ?? null;
        $ipgTransactionId = $response->ipgTransactionId ?? null;
        $orderId = $response->orderId ?? null;

        $last4 = isset($response->paymentMethodDetails->paymentCard->last4)
            ? $response->paymentMethodDetails->paymentCard->last4
            : '';
        $brand = isset($response->paymentMethodDetails->paymentCard->brand)
            ? $response->paymentMethodDetails->paymentCard->brand
            : '';
        $response_code_3dsecure = isset($response->secure3dResponse->responseCode3dSecure)
            ? $response->secure3dResponse->responseCode3dSecure
            : '';
        $streetMatch = isset($response->processor->avsResponse->streetMatch)
            ? $response->processor->avsResponse->streetMatch
            : '';
        $postalCodeMatch = isset($response->processor->avsResponse->postalCodeMatch)
            ? $response->processor->avsResponse->postalCodeMatch
            : '';

        $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' .
            $this->helper->getResponseMessage($response_code_3dsecure) . '|' . $orderId;

        $paymentModel->setData('remote_status_or_code', $transactionStatus);
        $paymentModel->setData('approval_code', $approvalCode);
        $paymentModel->setData('response_3ds_code_message', $response_code_3dsecure);
        $paymentModel->setData('brand', $brand);
        $paymentModel->setData('last4', $last4);
        $paymentModel->setData('ipgTransactionId', $ipgTransactionId);
        $paymentModel->setData('remote_message', $transactionMessage);
        $paymentModel->setData('street_match', $streetMatch);
        $paymentModel->setData('postcode_match', $postalCodeMatch);

        if (in_array($transactionStatus, ['DECLINED', 'FAILED', 'VALIDATION_FAILED'])) {
            $paymentModel->setData('status', 4);
        } elseif ($this->helper->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
            $currentOrder = $this->getCurrentOrder();

            if ($currentOrder && $currentOrder->getId()) {
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $currentOrder->getPayment();

                $paymentAction = $this->config->getConfig('payment/lcnetpaymentjs/payment_action');

                if ($paymentAction === 'authorize') {
                    $this->helper->addLog('Payment Action: authorize - Setting order to pending_payment');

                    // Set order status to pending payment (authorized)
                    $currentOrder->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                    $currentOrder->setStatus('pending_payment');

                    $payment->setIsTransactionPending(false);
                    $payment->setIsTransactionClosed(false);
                    $this->orderPaymentRepository->save($payment);
                    $this->orderRepository->save($currentOrder);
                    $paymentModel->setData('status', 1);

                } else {
                    $this->helper->addLog('Payment Action: authorize capture - Setting order to processing');

                    // Ensure payment is not marked as pending to avoid payment review status
                    $payment->setIsTransactionPending(false);
                    $payment->setIsTransactionClosed(false);
                    $this->orderPaymentRepository->save($payment);
                    $this->helper->processOrder($currentOrder, 0, 1);
                    $paymentModel->setData('status', 2);
                    $saveCard = $paymentModel->getData('save_card');
                    $payment = $currentOrder->getPayment();
                    $methodCode = $payment->getMethod();

                    if (!empty($saveCard) && $currentOrder->getCustomerId() && $methodCode === 'lcnetpaymentjs') {
                        $quoteId = $currentOrder->getQuoteId();

                        $paymentTokenObj = $this->paymentTokenCollectionFactory->create()
                            ->addFieldToFilter('details', ['like' => '%"quote_id":"' . $quoteId . '"%']);

                        $tokenData = '';
                        foreach ($paymentTokenObj as $token) {
                            $details = $token->getTokenDetails();
                            $paymentTokenDetails = $this->serializer->unserialize($details);
                            if (isset($paymentTokenDetails['quote_id']) &&
                                $paymentTokenDetails['quote_id'] == $quoteId &&
                                isset($paymentTokenDetails['paymentjs_token'])
                            ) {
                                $tokenData = $paymentTokenDetails['paymentjs_token'];
                                break;
                            }
                        }

                        $paymentJsTokenData = (!empty($tokenData)) ? $tokenData : '';

                        if (!empty($paymentJsTokenData)) {
                            $paymentTokenData = [
                                'customer_id' => $currentOrder->getCustomerId(),
                                'payment_token' => $paymentJsTokenData['token'],
                                'masked' => $paymentJsTokenData['masked'],
                                'brand' => $paymentJsTokenData['brand'],
                                'last4' => $paymentJsTokenData['last4'],
                                'exp_month' => $paymentJsTokenData['exp_month'],
                                'exp_year' => $paymentJsTokenData['exp_year'],
                            ];

                            if (isset($response->schemeTransactionId) && !empty($response->schemeTransactionId)) {
                                $paymentTokenData['scheme_transaction_id'] = $response->schemeTransactionId ?? null;
                            }

                            $this->helper->savePaymentToken($paymentTokenData);
                        }
                    }

                    $this->checkoutSession->setForceOrderMailSentOnSuccess(true);
                }
            }
        }

        $this->paymentsRepository->save($paymentModel);
    }

    /**
     * Get API payload for PATCH request
     *
     * @param \Magento\Sales\Model\Order $currentOrder
     * @param array $config
     * @return array
     */
    private function getPayload($currentOrder, $config)
    {
        $store_id = $config['store_id'];
        $billingAddress = $currentOrder->getBillingAddress();

        $country = $billingAddress->getCountryId();
        $street = implode(' ', $billingAddress->getStreet());
        $city = $billingAddress->getCity();
        $state = $billingAddress->getRegion();
        $zipCode = $billingAddress->getPostcode();

        $threeDSObj = $this->paymentTokenCollectionFactory->create()
            ->addFieldToFilter('details', ['like' => '%"orderId":"' . $currentOrder->getId() . '"%']);

        $threeDSMethodData = 'No';
        foreach ($threeDSObj->getItems() as $token) {
            $details = $token->getData('details');
            $paymentTokenDetails = $this->serializer->unserialize($details);
            if (isset($paymentTokenDetails['orderId']) &&
                $paymentTokenDetails['orderId'] == $currentOrder->getId() &&
                isset($paymentTokenDetails['threeDSMethodData'])
            ) {
                $threeDSMethodData = $paymentTokenDetails['threeDSMethodData'];
            }
        }

        $methodNotification = ($threeDSMethodData == 'Yes') ? 'RECEIVED' : 'EXPECTED_BUT_NOT_RECEIVED';

        return [
            "authenticationType" => "Secure3DAuthenticationUpdateRequest",
            "storeId" => $store_id,
            "billingAddress" => [
                "address1" => $street,
                "city" => $city,
                "region" => $state,
                "postalCode" => $zipCode,
                "country" => $country
            ],
            "methodNotificationStatus" => $methodNotification,
        ];
    }
}
