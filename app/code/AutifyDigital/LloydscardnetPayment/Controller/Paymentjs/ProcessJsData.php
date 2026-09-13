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

use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class Process Payment JS Data
 */
class ProcessJsData extends \AutifyDigital\LloydscardnetPayment\Controller\Index\AbstractAction
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
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     * */
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
     * @var \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory
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
     * ProcessJsData constructor
     *
     * @param Context $context
     * @param HelperData $helperData
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param OrderFactory $orderFactory
     * @param \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory
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
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        Config $config,
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory,
        LayoutFactory $resultLayoutFactory,
        OrderFactory $orderFactory,
        \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory,
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
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $returnData = ['error' => false, 'message' => ''];
        $this->helper->addLog('ProcessJsData Controller call');
        $resultJson = $this->resultJsonFactory->create();
        $currentOrder = null;
        try {
            $currentOrder = $this->getCurrentOrder();
            $mode = $this->config->getConfig('payment/basic/lloyds_mode');
            $config = $this->initializeReDirectPaymentParameters($mode);
        } catch (\Exception $e) {
            $this->helper->restoreQuote();
            $this->helper->addLog('ProcessJsData Order Exception1: ' . $e->getMessage());
            $this->messageManager->addErrorMessage((string) __('Something went wrong'));
            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
            $resultJson->setData($returnData);
            return $resultJson;
        }

        if (!$currentOrder || !$currentOrder->getIncrementId()) {
            $this->helper->restoreQuote();
            $this->helper->addLog('ProcessJsData: Order Id not found');
            $this->messageManager->addWarningMessage((string) __("Invalid payment request!"));
            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
            $resultJson->setData($returnData);
            return $resultJson;
        }

        $paymentToken = $this->getPaymentToken();

        if (!$paymentToken) {
            $this->helper->addLog('ProcessJsData: paymentToken not found');
            $this->helper->restoreQuote();
            $this->messageManager->addErrorMessage('Something went wrong. Payment token is not found.');
            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
            $resultJson->setData($returnData);
            return $resultJson;
        }

        try {
            if (substr($config['store_id'], 0, 2) !== '22') {
                $this->helper->addLog('Invalid store ID : ' . $config['store_id']);
                $this->helper->restoreQuote();
                $returnData['error'] = true;
                $returnData['message'] = 'Invalid storeId. Please contact support.';
                $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                return $resultJson->setData($returnData);
            }

            /** @var \Magento\Sales\Model\Order $currentOrder */
            $callArray = $this->getPayload($currentOrder, $config, $paymentToken);
            $total = number_format((float)$currentOrder->getGrandTotal(), 2, '.', '');
            if ($total <= 0) {
                $total = number_format((float)$currentOrder->getBaseGrandTotal(), 2, '.', '');
            }

            $this->helper->addLog($callArray, true);
            $curlCall = $this->helper->callCurl(HelperData::PAYMENTS_API_URL, $callArray, 'POST');
            $this->helper->addLog($curlCall, true);

            $paymentModel = $this->lcPaymentsFactory->create();
            
            if (isset($curlCall['status']) && $curlCall['status'] != 'error') {
                $response = $curlCall['response'];
                try {
                    $lloyds_response_payer_id = $response->orderId ?? null;
                    $saveCard = $this->getRequest()->getParam('save_card') == 'true' ? 1 : 0;
                    $paymentCode = $this->lcnetPaymentjs->getCode();

                    $paymentModel->setData('amount', $total);
                    $paymentModel->setData('payment_method', $paymentCode);
                    $paymentModel->setData('status', 1);
                    $paymentModel->setData('order_id', $currentOrder->getId());
                    $paymentModel->setData('order_increment_id', $currentOrder->getIncrementId());
                    $paymentModel->setData('remote_reference', $callArray['merchantTransactionId']);
                    $paymentModel->setData('save_card', $saveCard);
                    $paymentModel->setData('cardnet_order_id', $lloyds_response_payer_id);
                    $this->paymentsRepository->save($paymentModel);
                } catch (\Exception $e) {
                    $this->helper->addLog('ProcessJsData Ex: ' . $e->getMessage());
                    $this->helper->restoreQuote();
                    $this->messageManager->addErrorMessage('Something went wrong.');
                    $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                    $resultJson->setData($returnData);
                    return $resultJson;
                }

                if (isset($response->transactionStatus)) {
                    
                    $lloyds_response_transaction_ref = $response->ipgTransactionId ?? null;

                    $approvalCode = $response->approvalCode ?? null;
                    $transactionStatus = $response->transactionStatus ?? null;

                    $last4 = (isset($response->paymentMethodDetails) &&
                    isset($response->paymentMethodDetails->paymentCard) &&
                    isset($response->paymentMethodDetails->paymentCard->last4)) ?
                    $response->paymentMethodDetails->paymentCard->last4 : '';

                    $brand = (isset($response->paymentMethodDetails) &&
                    isset($response->paymentMethodDetails->paymentCard) &&
                    isset($response->paymentMethodDetails->paymentCard->brand)) ?
                    $response->paymentMethodDetails->paymentCard->brand : '';

                    $response_code_3dsecure = (isset($response->secure3dResponse) &&
                    isset($response->secure3dResponse->responseCode3dSecure)) ?
                    $response->secure3dResponse->responseCode3dSecure : '';

                    $orderId = $response->orderId ?? null;
                    $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' .
                    $this->helper->getResponseMessage($response_code_3dsecure) . '|' . $orderId;
                    
                    $streetMatch = isset($response->processor->avsResponse->streetMatch) ?
                    $response->processor->avsResponse->streetMatch : '';
                    $postalCodeMatch = isset($response->processor->avsResponse->postalCodeMatch) ?
                    $response->processor->avsResponse->postalCodeMatch : '';

                    $paymentModel->setData('remote_status_or_code', $transactionStatus);
                    $paymentModel->setData('approval_code', $approvalCode);
                    $paymentModel->setData('response_3ds_code_message', $response_code_3dsecure);
                    $paymentModel->setData('brand', $brand);
                    $paymentModel->setData('last4', $last4);
                    $paymentModel->setData('ipgTransactionId', $lloyds_response_transaction_ref);
                    $paymentModel->setData('remote_message', $transactionMessage);
                    $paymentModel->setData('street_match', $streetMatch);
                    $paymentModel->setData('postcode_match', $postalCodeMatch);
                    if (in_array($transactionStatus, ['DECLINED', 'FAILED', 'VALIDATION_FAILED'])) {
                        $paymentModel->setData('status', 4);
                    }
                    $this->paymentsRepository->save($paymentModel);

                    /** @var \Magento\Sales\Model\Order\Payment $payment */
                    $payment = $currentOrder->getPayment();
                    $payment->setTransactionId($lloyds_response_transaction_ref);
                    $payment->setLastTransId($lloyds_response_transaction_ref);
                    $payment->setIsTransactionClosed(false);
                    $payment->setIsTransactionPending(true);
                    $this->orderRepository->save($currentOrder);

                    if ($this->helper->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
                        $this->helper->addLog('ProcessJsData Status: Approved');

                        $paymentModel->setData('status', 2);
                        $this->paymentsRepository->save($paymentModel);
                        $paymentAction = $this->config->getConfig('payment/lcnetpaymentjs/payment_action');
                        if ($paymentAction === 'authorize') {
                            // Set order status to pending payment (authorized) but not pending review
                            $currentOrder->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                            $currentOrder->setStatus('pending_payment');

                            // Ensure payment is not marked as pending to avoid review status
                            $payment->setIsTransactionPending(false);
                            $payment->setIsTransactionClosed(false);
                            $this->orderRepository->save($currentOrder);

                            $paymentModel->setData('status', 1);
                            $this->paymentsRepository->save($paymentModel);
                            
                            $this->messageManager->addSuccessMessage((string)__(
                                'Payment authorized successfully. Amount will be captured when order is shipped.'
                            ));
                        } else {
                             $quoteId = $currentOrder->getQuoteId();

                            if ($currentOrder->getCustomerId() && $saveCard
                            ) {
                                $paymentTokenObj = $this->paymentTokenCollectionFactory->create()
                                    ->addFieldToFilter('details', ['like' => '%"quote_id":"' . $quoteId . '"%']);

                                $tokenData = '';
                                foreach ($paymentTokenObj as $token) {
                                    $details = $token->getTokenDetails();
                                    $paymentTokenDetails = $this->serializer->unserialize($details);
                                    if (isset($paymentTokenDetails['quote_id']) &&
                                        $paymentTokenDetails['quote_id'] == $quoteId
                                    ) {
                                        $tokenData = $paymentTokenDetails['paymentjs_token'];
                                        break;
                                    }
                                }

                                $paymentJsTokenData =  (!empty($tokenData)) ? $tokenData : '';
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
                        if (!$currentOrder->getEmailSent()) {
                            $this->orderSender->send($currentOrder);
                        }
                        /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
                        $orderPayment = $currentOrder->getPayment();
                        $paymentMethodTitle = $orderPayment->getMethodInstance()->getTitle();
                        $this->messageManager
                        ->addSuccessMessage((string) __('Order processed successfully by %1.', $paymentMethodTitle));
                        $returnData['url'] = $this->_baseUrl . 'checkout/onepage/success';

                        $this->helper->processOrder($currentOrder);
                        $resultJson->setData($returnData);
                        return $resultJson;
                    } elseif (strpos((string)$approvalCode, 'waiting 3dsecure') !== false && $transactionStatus === 'WAITING') {
                        $this->helper->addLog('ProcessJsData Status: waiting');
                        $ipgTransactionId = $response->ipgTransactionId ?? null;

                        $termURL = $acsURL = $cReq = '';
                        if (isset($response->authenticationResponse->params)) {
                            $authenticationResponse = $response->authenticationResponse->params;
                            $termURL = $authenticationResponse->termURL;
                            $acsURL = $authenticationResponse->acsURL;
                            $cReq = $authenticationResponse->cReq;
                        }

                        if (!empty($termURL) && !empty($acsURL) && !empty($cReq)) {
                            $this->helper->addLog('ProcessJsData Status: Custom form');
                            // Submit the 3DS challenge through the same-origin ChallengeFrame
                            // controller, which dynamically whitelists the bank ACS host in the
                            // CSP "form-action" policy. Submitting the cross-origin ACS URL
                            // directly from the checkout page is blocked by the static CSP.
                            $returnData['form_data'] = $this->helper
                                ->buildChallengeFormData((string)$acsURL, (string)$cReq, (string)$termURL);
                            $resultJson->setData($returnData);
                            return $resultJson;
                        } elseif (isset($response->authenticationResponse->secure3dMethod) &&
                            isset($response->authenticationResponse->secure3dMethod->methodForm)
                        ) {
                            $this->helper->addLog('ProcessJsData Status: Iframe');
                            $methodForm = $response->authenticationResponse->secure3dMethod->methodForm;
                            $this->saveThreeDSMethodDataWithOrderId($this->serializer->serialize($methodForm));
                            //Iframe
                            $returnData['3dsframe'] = true;
                            $returnData['data'] = $this->encryptor->encrypt($methodForm);
                            $returnData['ipg_transaction_id'] = $this->encryptor
                            ->encrypt($lloyds_response_transaction_ref);
                            $returnData['ipg_id'] = $lloyds_response_transaction_ref;
                            $returnData['order_id'] = $currentOrder->getId();
                            $resultJson->setData($returnData);
                            return $resultJson;
                        } else {
                            $this->helper->addLog('ProcessJsData Status: Error');
                            $paymentModel->setData('status', 4);
                            $paymentModel->setData('remote_status_or_code', $transactionStatus ? $transactionStatus : 'ERROR');
                            $paymentModel->setData('remote_message', 'WAITING 3DS - No authentication response data');
                            $this->paymentsRepository->save($paymentModel);
                            $this->helper->restoreQuote();
                            if ($currentOrder->canCancel()) {
                                $this->helper->cancelOrder($currentOrder);
                                $this->messageManager->addErrorMessage((string)__('There is an error with the payment.'.
                                ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.'));
                            }
                            $this->messageManager->addErrorMessage((string)__("Something went wrong with the payment.".
                            " Please contact to customer support."));
                            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                            $resultJson->setData($returnData);
                            return $resultJson;
                        }
                    } else {
                        /*Display a user friendly Error on the page using any of the
                        following error information returned by Lloyds Cards Net */
                        $paymentModel->setData('status', 4);
                        $paymentModel->setData('remote_status_or_code', $transactionStatus ?? 'ERROR');
                        $paymentModel->setData('remote_message', ($approvalCode ?? '') . '|' . ($transactionStatus ?? '') . '|Payment not approved');
                        $this->paymentsRepository->save($paymentModel);
                        $this->messageManager->addErrorMessage((string)__("Something went wrong with the payment." .
                        " Please contact to customer support."));
                        $this->helper->restoreQuote();
                        if ($currentOrder->canCancel()) {
                            $this->helper->cancelOrder($currentOrder);
                            $this->messageManager->addErrorMessage((string) __('There is an error with the payment.' .
                            ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.'));
                        }
                        $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                        $resultJson->setData($returnData);
                        return $resultJson;
                    }
                } else {
                    /*Display a user friendly Error on the page using any of the
                    following error information returned by Lloyds Cards Net */
                    $paymentModel->setData('status', 4);
                    $paymentModel->setData('remote_status_or_code', 'ERROR');
                    $paymentModel->setData('remote_message', 'No transactionStatus in gateway response');
                    $this->paymentsRepository->save($paymentModel);
                    $this->messageManager->addErrorMessage((string) __("Something went wrong with the payment." .
                    " Please contact to customer support."));
                    $this->helper->restoreQuote();
                    if ($currentOrder->canCancel()) {
                        $this->helper->cancelOrder($currentOrder);
                        $this->messageManager->addErrorMessage((string) __('There is an error with the payment.' .
                        ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.'));
                    }
                    $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                    $resultJson->setData($returnData);
                    return $resultJson;
                }
            } else {
                if (isset($curlCall['response']) && !empty($curlCall['response'])
                    && isset($curlCall['response']->errorMessage) && !empty($curlCall['response']->errorMessage)
                ) {
                    $this->messageManager->addErrorMessage($curlCall['response']->errorMessage);
                }
                $response = $curlCall['response'] ?? new \stdClass();
                $approvalCode = $response->approvalCode ?? '';
                $transactionStatus = $response->transactionStatus ?? '';
                $orderId = $response->orderId ?? '';
                $merchantTransactionId = $response->merchantTransactionId ?? '';
                $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $orderId;
                $paymentModel->setData('amount', $total);
                $paymentModel->setData('order_id', $currentOrder->getId());
                $paymentModel->setData('order_increment_id', $currentOrder->getIncrementId());
                $paymentModel->setData('remote_reference', $merchantTransactionId);
                $paymentModel->setData('cardnet_order_id', $orderId);
                $paymentModel->setData('remote_status_or_code', $transactionStatus);
                $paymentModel->setData('approval_code', $approvalCode);
                $paymentModel->setData('remote_message', $transactionMessage);
                $paymentModel->setData('status', 4);
                $this->paymentsRepository->save($paymentModel);
                $this->helper->addLog('There is some error in curl response.');
                $this->helper->restoreQuote();

                if ($currentOrder->canCancel()) {
                    $this->helper->cancelOrder($currentOrder);
                    $this->messageManager->addErrorMessage((string) __('There is an error with the payment.' .
                    ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.'));
                }

                $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                $resultJson->setData($returnData);
                return $resultJson;
            }
        } catch (\Exception $e) {
            $this->helper->restoreQuote();
            $returnData = [
                'error' => true,
                'message' => "Something went wrong."
            ];

            $this->helper->addLog('ProcessJsData Exception: ' . $e->getMessage());

            $this->messageManager->addErrorMessage('Something went wrong.');
            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
        }
        return $resultJson->setData($returnData);
    }

    /**
     * Get payment token from request or cache
     *
     * @return string|array|null
     */
    private function getPaymentToken()
    {
        $currentOrder = $this->getCurrentOrder();
        $quoteId = $currentOrder->getQuoteId();
        $tokenIdParam = $this->getRequest()->getParam('token_id');
        $token_id = $tokenIdParam ? $this->encryptor->decrypt($tokenIdParam) : '';
        $this->helper->addLog('ProcessJsData: token_id: ' . $token_id);

        if ($token_id) {
            return $this->helper->getTokenValue($token_id);
        }

        $paymentTokenObj = $this->paymentTokenCollectionFactory->create()
            ->addFieldToFilter('details', ['like' => '%"quote_id":"' . $quoteId . '"%']);
        ;

        $tokenData = '';
        foreach ($paymentTokenObj as $token) {
            $details = $token->getDetails();
            $paymentTokenDetails = $this->serializer->unserialize($details);
            if (isset($paymentTokenDetails['quote_id']) && $paymentTokenDetails['quote_id'] == $quoteId) {
                $tokenData = $token->getGatewayToken();
                break;
            }
        }

        return $tokenData;
    }

    /**
     * Get API Payload
     *
     * @param \Magento\Sales\Model\Order $currentOrder
     * @param Array $config
     * @param String $paymentToken
     * @return array
     */
    private function getPayload($currentOrder, $config, $paymentToken)
    {
        $storeId = $config['store_id'];
        $paymentAction = $this->helper->getConfig('payment/lcnetpaymentjs/payment_action');

        if ($paymentAction === 'authorize') {
            $requestType = 'PaymentTokenPreAuthTransaction';
        } else {
            $requestType = 'PaymentTokenSaleTransaction';
        }

        if (!$this->helper->startsWith((string)$storeId, '22')) {
            $this->helper->addLog('ProcessJsData: Invalid store ID - ' . $storeId);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid storeId. Please contact support.')
            );
        }

        $transactionCode = $currentOrder->getIncrementId() . '-' . time();
        $transactionOrigin = 'ECOM';
        $total = number_format((float)$currentOrder->getGrandTotal(), 2, '.', '');
        if ($total <= 0) {
            $total = number_format((float)$currentOrder->getBaseGrandTotal(), 2, '.', '');
        }
        $ccy_code = $currentOrder->getOrderCurrencyCode();
        $transactionCode = $currentOrder->getIncrementId() . '-' . time();
        $currency = $this->helper->getIsoCurrencyCodeFromCurrencyCode($ccy_code);
       
        $merchantTransactionId = $this->helper->generateMerchantTransactionId();

        $authenticationType = 'Secure3DAuthenticationRequest';
        $termURL = $config['term_url'];
        $methodNotificationURL =  $config['method_notification_url'];
        $challengeWindowSize =  '01';
        $comments = 'AutifyDigital LBOP Magento PaymentJS';
        $invoiceNumber = $currentOrder->getIncrementId();

        $enablePONumber = $this->config->getConfig('payment/basic/active_po_number');
        $configPONumber = $this->config->getConfig('payment/basic/dynamic_data_po_number');
        $saveCard = $this->getRequest()->getParam('save_card');

        if ($saveCard == "true") {
            $sequence = 'FIRST';
            $challengeIndicator = '04';
        } else {
            $sequence = 'SUBSEQUENT';
            $challengeIndicator = $this->config->getConfig('payment/basic/challenge_indicator');
        }
        
        $tokenIdParam = $this->getRequest()->getParam('token_id');
        $token_id = $tokenIdParam ? $this->encryptor->decrypt($tokenIdParam) : '';

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

        $billingAddress = $currentOrder->getBillingAddress();
        $shippingAddress = $currentOrder->getShippingAddress();

        // If cart is virtual
        if (!$shippingAddress || $shippingAddress->getFirstname() == null) {
            $shippingAddress = $currentOrder->getBillingAddress();
        }

        $billingName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $shippingName = $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();

        $email = $billingAddress->getEmail();
        if (!$email) {
            $email = $currentOrder->getCustomerEmail();
        }

        // Billing Address
        $country = $billingAddress->getCountryId();
        $street = implode(' ', $billingAddress->getStreet());
        $billingPhone = $billingAddress->getTelephone();
        $city = $billingAddress->getCity();
        $state = $billingAddress->getRegion();
        $zipCode = $billingAddress->getPostcode();
        $company = $billingAddress->getCompany();

        // Shipping Address
        $shipCountry = $shippingAddress->getCountryId();
        $shipStreet = implode(' ', $shippingAddress->getStreet());
        $shipBillingPhone = $shippingAddress->getTelephone();
        $shipCity = $shippingAddress->getCity();
        $shipState = $shippingAddress->getRegion();
        $shipZipCode = $shippingAddress->getPostcode();
        $shipCompany = $shippingAddress->getCompany();

        $screen_height =
        !empty($this->getRequest()->getParam('screen_height')) ? $this->getRequest()->getParam('screen_height') : 912;
        $screen_width =
        !empty($this->getRequest()->getParam('screen_width')) ? $this->getRequest()->getParam('screen_width') : 1920;

        $callArray = [
            "requestType" => $requestType,
            "storeId" => $storeId,
            "merchantTransactionId" => $merchantTransactionId,
            "transactionOrigin" => $transactionOrigin,
            "transactionAmount" => [
                "total" => $total,
                "currency" => $currency
            ],
            "paymentMethod" => [
                "paymentToken" => [
                    "value" => $paymentToken
                ],
            ],
            "authenticationRequest" => [
                "authenticationType" => $authenticationType,
                "termURL" => $termURL,
                "methodNotificationURL" => $methodNotificationURL,
                "challengeIndicator" => $challengeIndicator,
                "challengeWindowSize" => $challengeWindowSize,
                "cardHolderBrowserParams" => [
                    "browserIP" => $this->helper->getRemoteIp(),
                    "browserLanguage" => substr($this->helper->getStore()->getLocale(), 0, 2),
                    "browserScreenHeight" => $screen_height,
                    "browserScreenWidth" => $screen_width,
                    "browserTimeZone" => $this->helper->timezone()->date()->format('Z'),
                    "browserUserAgent" => $this->helper->getHttpHeader()->getHttpUserAgent()
                ]
            ],
            "order" => [
                "billing" => [
                    "name" => $billingName,
                    "address" => [
                        "company" => $company,
                        "address1" => $street,
                        "city" => $city,
                        "region" => $state,
                        "postalCode" => $zipCode,
                        "country" => $country
                    ],
                    "contact" => [
                        "phone" => $billingPhone,
                        "email" => $email
                    ],
                ],
                "shipping" => [
                    "name" => $shippingName,
                    "address" => [
                        "company" => $shipCompany,
                        "address1" => $shipStreet,
                        "city" => $shipCity,
                        "region" => $shipState,
                        "postalCode" => $shipZipCode,
                        "country" => $shipCountry
                    ],
                ],
                "additionalDetails" => [
                    "comments" => $comments,
                    "invoiceNumber" => $invoiceNumber,
                    "purchaseOrderNumber" => $ponumber
                ],
                "orderId" => $this->helper->getOrderIdWithSuffix($currentOrder->getIncrementId())
            ],
            "storedCredentials" => [
                "sequence" => $sequence,
                "scheduled" => false,
                "initiator" => "CARDHOLDER"
            ]
        ];

        return $callArray;
    }

    /**
     * Save the 3DS Method Data and Order ID in the vault_payment_token table
     *
     * @param array|string $methodFormData
     * @return void
     */
    private function saveThreeDSMethodDataWithOrderId($methodFormData = null): void
    {

        try {
            $orderIncId = $this->checkoutSession->getLastRealOrderId();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderCollectionFactory->create()
                ->addFieldToFilter('increment_id', $orderIncId)
                ->setPageSize(1)
                ->getFirstItem();
            $orderId = $order->getId();

            $quote = $this->checkoutSession->getQuote();
            $customerId = $quote->getCustomerId() ? $quote->getCustomerId() : null;

            $paymentToken = $this->paymentTokenFactory->create();
            $createdAt = $this->dateTime->date('Y-m-d H:i:s');
            $paymentCode = $this->lcnetPaymentjs->getCode();

            $publicHash = hash('sha256', $customerId . $paymentCode . $createdAt);
            $gatewayToken = 'gateway_token_' . hash('sha256', (string)$orderId);

            $tokenDetails = [];
            if ($methodFormData !== null) {
                $tokenDetails['threeDSMethodData'] = $methodFormData;
            }
            $tokenDetails['orderId'] = $orderId;
            $paymentToken->setTokenDetails($this->serializer->serialize($tokenDetails));
            
            $paymentToken->setCustomerId($customerId)
                ->setWebsiteId((int)$this->storeManager->getStore()->getWebsiteId())
                ->setPublicHash($publicHash)
                ->setPaymentMethodCode($paymentCode)
                ->setGatewayToken($gatewayToken)
                ->setType('lloydscardnet')
                ->setIsActive(true)
                ->setIsVisible(true)
                ->setCreatedAt($createdAt)
                ->setExpiresAt('');

            $this->paymentTokenRepository->save($paymentToken);

        } catch (\Exception $e) {
            $this->helper->addLog($e->getMessage(), true);
        }
    }
}
