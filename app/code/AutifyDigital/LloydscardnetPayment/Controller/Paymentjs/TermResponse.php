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
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory;
use Magento\Framework\Session\SessionManagerInterface;

class TermResponse extends \AutifyDigital\LloydscardnetPayment\Controller\Index\AbstractAction
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
     * @param ApiHelper $helperData
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
        ApiHelper $helperData,
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
     * Execute Function
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $this->helper->addLog('PaymentJs TermResponse Start');
        $this->helper->addLog($this->getRequest()->getParams(), true);

        $orderId = (null !== $this->getRequest()->getParam('order_id')) ?
        $this->getRequest()->getParam('order_id') : '';
        $cres = (null !== $this->getRequest()->getParam('cres')) ? $this->getRequest()->getParam('cres') : '';
        $threeDsReceived = 0;

        //To check if the CRES is received earlier, if we have received it, we will call an API to get the latest response.
        if (empty($orderId) && empty($cres)) {
            $this->helper->restoreQuote();
            $this->messageManager->addErrorMessage((string) __("Something went wrong."));
            return $this->_redirect($this->_baseUrl . 'checkout/cart/');
        }

        try {
            $paymentModel = $this->helper->getPaymentByOrderId($orderId);
            if (!$paymentModel || !$paymentModel->getId()) {
                $this->messageManager->addErrorMessage((string) __("Order not found."));
                return $this->_redirect($this->_baseUrl . 'checkout/cart/');
            }

            $orderId = $paymentModel->getOrderId();
            $mode = $this->config->getConfig('payment/basic/lloyds_mode');
            $config = $this->initializeReDirectPaymentParameters($mode);
            /** @var \Magento\Sales\Model\Order $currentOrder */
            $currentOrder = $this->orderRepository->get($orderId);

            // Update threeds_received field when cres is received (if order is not already complete/processing)
            if ($paymentModel && $paymentModel->getId()) {
                $orderState = $currentOrder->getState();
                $currentThreedsCount = (int) $paymentModel->getThreedsReceived();
                
                $this->helper->addLog('TermResponse - Current threeDSReceived: ' . $currentThreedsCount .
                    ', Order State: ' . $orderState);
                
                // If already processed (threeds >= 2), prevent duplicate processing
                if ($currentThreedsCount >= 2) {
                    $this->helper->addLog('TermResponse: Order ' . $orderId .
                        ' already processed. ThreeDSReceived: ' . $currentThreedsCount);
                    
                    if ($orderState === \Magento\Sales\Model\Order::STATE_PROCESSING ||
                        $orderState === \Magento\Sales\Model\Order::STATE_COMPLETE) {
                        $this->helper->addLog('Order is in success state, redirecting to success page');
                        $this->checkoutSession->setLastQuoteId($currentOrder->getQuoteId());
                        $this->checkoutSession->setLastSuccessQuoteId($currentOrder->getQuoteId());
                        $this->checkoutSession->setLastOrderId($currentOrder->getId());
                        $this->checkoutSession->setLastRealOrderId($currentOrder->getIncrementId());
                        /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
                        $orderPayment = $currentOrder->getPayment();
                        $paymentMethodTitle = $orderPayment->getMethodInstance()->getTitle();
                        $this->messageManager->addSuccessMessage(
                            (string) __('Order processed successfully by %1.', $paymentMethodTitle)
                        );
                        return $this->_redirect($this->_baseUrl . 'checkout/onepage/success/');
                    } else {
                        $this->messageManager->addErrorMessage((string) __('Order was already processed.'));
                        return $this->_redirect($this->_baseUrl . 'checkout/cart/');
                    }
                }

                // Update threeds_received field when cres is received (if order is not already complete/processing)
                if ($orderState !== \Magento\Sales\Model\Order::STATE_PROCESSING &&
                    $orderState !== \Magento\Sales\Model\Order::STATE_COMPLETE) {
                    $threeDsReceived = $currentThreedsCount + 1;
                    $paymentModel->setThreedsReceived($threeDsReceived);
                    $this->paymentsRepository->save($paymentModel);
                    $this->helper->addLog('3DS received count updated to: ' . $threeDsReceived . ' for order: ' . $orderId);
                } else {
                    $this->helper->addLog('Order ' . $orderId . ' is in ' . $orderState .
                        ' state. Already processed successfully.');
                    $this->checkoutSession->setLastQuoteId($currentOrder->getQuoteId());
                    $this->checkoutSession->setLastSuccessQuoteId($currentOrder->getQuoteId());
                    $this->checkoutSession->setLastOrderId($currentOrder->getId());
                    $this->checkoutSession->setLastRealOrderId($currentOrder->getIncrementId());
                    /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
                    $orderPayment = $currentOrder->getPayment();
                    $paymentMethodTitle = $orderPayment->getMethodInstance()->getTitle();
                    $this->messageManager->addSuccessMessage(
                        (string) __('Order processed successfully by %1.', $paymentMethodTitle)
                    );
                    return $this->_redirect($this->_baseUrl . 'checkout/onepage/success/');
                }
            }

            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $currentOrder->getPayment();
            $method = $payment->getMethodInstance();
            $methodCode = $payment->getMethod();

            $allowedMethods = ['lcnetpaymentjs', 'cardnetgooglepay'];

            if (!in_array($methodCode, $allowedMethods)) {
                $this->messageManager->addErrorMessage((string) __(
                    "This order is not created by Lloyds Cardnet Payment."
                ));
                return $this->_redirect($this->_baseUrl . 'checkout/cart/');
            }

            $paymentReference = $currentOrder->getIncrementId();

            $lloydsOrderId = $paymentModel->getCardnetOrderId();
            $lloydsTransactionId = $paymentModel->getData('ipgTransactionId');

            $patchPayment = $this->getPayload($currentOrder, $config, $cres);

            $apiPath = '';
            if ($lloydsTransactionId && strpos(ApiHelper::PATCH_PAYMENTS_API_URL, '{ipgTransactionId}') !== false) {
                $apiPath = str_replace('{ipgTransactionId}', $lloydsTransactionId, ApiHelper::PATCH_PAYMENTS_API_URL);
            } else {
                $this->helper->setLloydsCookie('1');
                $this->messageManager->addErrorMessage((string) __("Something went wrong with the payment." .
                " Please contact to customer support."));
                $this->cancelOrder($paymentModel, $currentOrder, 'ERROR', 'Missing transaction ID for PATCH request');
                return $this->_redirect($this->_baseUrl . 'checkout/cart/');
            }

            $this->helper->addLog($patchPayment, true);
            $curlCall = $this->helper->callCurl($apiPath, $patchPayment, 'PATCH');
            $this->helper->addLog($curlCall, true);

            if (isset($curlCall['status']) && $curlCall['status'] != 'error') {
                $response = $curlCall['response'];

                if (!isset($response->transactionStatus)) {
                    $this->helper->setLloydsCookie('1');
                    $this->messageManager->addErrorMessage((string) __("Something went wrong with the payment." .
                    " Please contact to customer support."));
                    $this->cancelOrder($paymentModel, $currentOrder, 'ERROR', 'No transactionStatus in gateway response');
                    return $this->_redirect($this->_baseUrl . 'checkout/cart/');
                }

                $approvalCode = $response->approvalCode ?? null;
                $transactionStatus = $response->transactionStatus ?? null;

                try {
                    $lloyds_response_payer_id = $response->orderId ?? null;
                    $paymentModel->setData('cardnet_order_id', $lloyds_response_payer_id);
                    $this->paymentsRepository->save($paymentModel);
                } catch (\Exception $e) {
                    $this->cancelOrder($paymentModel, $currentOrder, 'ERROR', $e->getMessage());
                    $this->messageManager->addErrorMessage('Something went wrong.');
                    return $this->_redirect($this->_baseUrl . 'checkout/cart/');
                }

                $lloyds_response_transaction_ref = $response->ipgTransactionId ?? null;

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
                    $this->helper->addLog('TermResponse: Transaction ' . $transactionStatus . ', setting payment status to failed');
                }
                $this->paymentsRepository->save($paymentModel);

                if ($this->helper->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
                    $this->helper->addLog('TermResponse Status: Approved');

                    $paymentAction = $this->config->getConfig('payment/lcnetpaymentjs/payment_action');

                    if ($paymentAction === 'authorize') {
                        $paymentModel->setData('status', 1);
                    } else {
                        $paymentModel->setData('status', 2);
                    }

                    $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $orderId;

                    $paymentModel->setData('remote_status_or_code', $transactionStatus);
                    $paymentModel->setData('remote_message', $transactionMessage);
                    $this->paymentsRepository->save($paymentModel);

                    $payment = $currentOrder->getPayment();
                    $methodCode = $payment->getMethod();

                    if ($methodCode === 'lcnetpaymentjs') {
                        $quoteId = $currentOrder->getQuoteId();

                        $paymentTokenObj = $this->paymentTokenCollectionFactory->create()
                            ->addFieldToFilter('details', ['like' => '%"quote_id":"' . $quoteId . '"%']);

                        $cachedData = '';
                        $paymentJsTokenData = null;
                        foreach ($paymentTokenObj as $token) {
                            $details = $token->getDetails();
                            $paymentTokenDetails = $this->serializer->unserialize($details);
                            if (isset($paymentTokenDetails['quote_id']) && $paymentTokenDetails['quote_id'] == $quoteId) {
                                $cachedData = $paymentTokenDetails['paymentjs_token'];
                                break;
                            }
                        }

                        if (!empty($cachedData)) {
                            $paymentJsTokenData = $cachedData;
                        }

                        $saveCard = $paymentModel->getSaveCard();

                        $this->paymentsRepository->save($paymentModel);

                        if (!empty($paymentJsTokenData) && is_array($paymentJsTokenData)
                            && $currentOrder->getCustomerId() && $saveCard
                        ) {
                            $paymentTokenData = [
                                'customer_id' => $currentOrder->getCustomerId(),
                                'payment_token' => $paymentJsTokenData['token'] ?? '',
                                'masked' => $paymentJsTokenData['masked'] ?? '',
                                'brand' => $paymentJsTokenData['brand'] ?? '',
                                'last4' => $paymentJsTokenData['last4'] ?? '',
                                'exp_month' => $paymentJsTokenData['exp_month'] ?? '',
                                'exp_year' => $paymentJsTokenData['exp_year'] ?? '',
                            ];

                            if (isset($response->schemeTransactionId) && !empty($response->schemeTransactionId)) {
                                $paymentTokenData['scheme_transaction_id'] = $response->schemeTransactionId;
                            }
                            $this->helper->savePaymentToken($paymentTokenData);
                        }
                    }

                    $isPaymentJs = ($methodCode == 'lcnetpaymentjs') ? 1 : 0;
                    $this->helper->processOrder($currentOrder, 0, $isPaymentJs);

                    if (!$currentOrder->getEmailSent()) {
                        $this->orderSender->send($currentOrder);
                    }
                    /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
                    $orderPayment = $currentOrder->getPayment();
                    $paymentMethodTitle = $orderPayment->getMethodInstance()->getTitle();
                    $this->messageManager
                    ->addSuccessMessage((string) __('Order processed successfully by %1.', $paymentMethodTitle));

                    $this->checkoutSession->setLastQuoteId($currentOrder->getQuoteId());
                    $this->checkoutSession->setLastSuccessQuoteId($currentOrder->getQuoteId());
                    $this->checkoutSession->setLastOrderId($currentOrder->getId());
                    $this->checkoutSession->setLastRealOrderId($currentOrder->getIncrementId());

                    return $this->_redirect($this->_baseUrl . 'checkout/onepage/success/');
                } elseif (strpos((string)$approvalCode, 'waiting 3dsecure') !== false && $transactionStatus === 'WAITING') {
                    $this->helper->addLog('TermResponse Status: waiting');
                    $ipgTransactionId = $response->ipgTransactionId ?? null;

                    $termURL = $acsURL = $cReq = '';
                    if (isset($response->authenticationResponse->params)) {
                        $authenticationResponse = $response->authenticationResponse->params;
                        $termURL = $authenticationResponse->termURL;
                        $acsURL = $authenticationResponse->acsURL;
                        $cReq = $authenticationResponse->cReq;
                    }

                    if (!empty($termURL) && !empty($acsURL) && !empty($cReq)) {
                        $this->helper->addLog('TermResponse Status: Custom form');
                        // Submit the 3DS challenge through the same-origin ChallengeFrame
                        // controller, which dynamically whitelists the bank ACS host in the
                        // CSP "form-action" policy. Posting the cross-origin ACS URL directly
                        // from this page is blocked by the static CSP.
                        $postData = $this->helper
                            ->buildChallengeFormData((string)$acsURL, (string)$cReq, (string)$termURL);

                        $resultLayout = $this->resultLayoutFactory->create();
                        $resultLayout->addDefaultHandle();
                        $resultLayout->getLayout()->getUpdate()->load(['lloyds_paymentjs_transactionnotificationurl']);
                        /** @var \Magento\Framework\View\Element\AbstractBlock|false $block */
                        $block = $resultLayout->getLayout()->getBlock('paymentjs.form.block');
                        if ($block) {
                            $block->setData('form_data', $postData);
                        }
                        return $resultLayout;
                    } elseif (isset($response->authenticationResponse->secure3dMethod) &&
                            isset($response->authenticationResponse->secure3dMethod->methodForm)
                    ) {
                        $this->helper->addLog('TermResponse Status: Iframe');
                        //Iframe
                        $methodForm = $response->authenticationResponse->secure3dMethod->methodForm;
                        $returnData['3dsframe'] = true;
                        $returnData['data'] = $methodForm;
                        $returnData['ipg_transaction_id'] = $lloyds_response_transaction_ref;
                        $returnData['ipg_id'] = $lloyds_response_transaction_ref;

                        $resultLayout = $this->resultLayoutFactory->create();
                        $resultLayout->addDefaultHandle();
                        $resultLayout->getLayout()->getUpdate()->load(['lloyds_paymentjs_transactionnotificationurl']);
                        /** @var \Magento\Framework\View\Element\AbstractBlock|false $block */
                        $block = $resultLayout->getLayout()->getBlock('paymentjs.form.block');
                        if ($block) {
                            $block->setData('iframe_data', $methodForm);
                        }
                        return $this->_redirect($this->_baseUrl .
                        'lloyds/paymentjs/transactionNotificationUrl/ipg_id/' .
                        $lloyds_response_transaction_ref);
                    } else {
                        $this->helper->addLog('TermResponse Status: Error');
                        $this->messageManager->addErrorMessage((string) __("Something went wrong with the payment." .
                        " Please contact to customer support."));
                        $this->cancelOrder($paymentModel, $currentOrder, 'WAITING', 'WAITING 3DS - No authentication response data');
                        return $this->_redirect($this->_baseUrl . 'checkout/cart/');
                    }
                } else {
                    $this->helper->setLloydsCookie('1');
                    if (isset($curlCall['response']) && !empty($curlCall['response'])
                        && isset($curlCall['response']->errorMessage) && !empty($curlCall['response']->errorMessage)
                    ) {
                        $this->messageManager->addErrorMessage($curlCall['response']->errorMessage);
                    }
                    $gatewayMsg = ($approvalCode ?? '') . '|' . ($transactionStatus ?? '') . '|Payment not approved';
                    $this->cancelOrder($paymentModel, $currentOrder, $transactionStatus ?? 'ERROR', $gatewayMsg);
                    return $this->_redirect($this->_baseUrl . 'checkout/cart/');
                }
            } else {
                $this->helper->setLloydsCookie('1');
                if (isset($curlCall['response']) && !empty($curlCall['response'])
                    && isset($curlCall['response']->errorMessage) && !empty($curlCall['response']->errorMessage)
                ) {
                    $this->messageManager->addErrorMessage($curlCall['response']->errorMessage);
                }
                $response = $curlCall['response'] ?? new \stdClass();
                $approvalCode = $response->approvalCode ?? '';
                $transactionStatus = $response->transactionStatus ?? '';
                $orderId = $response->orderId ?? '';
                $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $orderId;

                $paymentModel->setData('remote_status_or_code', $transactionStatus);
                $paymentModel->setData('approval_code', $approvalCode);
                $paymentModel->setData('remote_message', $transactionMessage);
                $this->paymentsRepository->save($paymentModel);
                $this->cancelOrder($paymentModel, $currentOrder);
                return $this->_redirect($this->_baseUrl . 'checkout/cart/');
            }
        } catch (\Exception $e) {
            $this->helper->addLog('TermResponse Exception: ' . $e->getMessage());
            $this->helper->restoreQuote();
            $this->messageManager->addErrorMessage((string) __('Something went wrong'));
        }
        $this->helper->setLloydsCookie('1');
        return $this->_redirect($this->_baseUrl . 'checkout/cart/');
    }

    /**
     * Get API Payload
     *
     * @param \Magento\Sales\Model\Order $currentOrder
     * @param Array $config
     * @param String $cres
     * @return array
     */
    private function getPayload($currentOrder, $config, $cres)
    {
        $store_id = $config['store_id'];

        $billingAddress = $currentOrder->getBillingAddress();
        $shippingAddress = $currentOrder->getShippingAddress();
        $billName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();

        $email = $billingAddress->getEmail();
        if (!$email) {
            $email = $currentOrder->getCustomerEmail();
        }

        $country = $billingAddress->getCountryId();
        $street = implode(' ', $billingAddress->getStreet());
        $billingPhone = $billingAddress->getTelephone();
        $city = $billingAddress->getCity();
        $state = $billingAddress->getRegion();
        $zipCode = $billingAddress->getPostcode();
        $company = $billingAddress->getCompany();

        $patchPayment = [
            "authenticationType" => "Secure3DAuthenticationUpdateRequest",
            "storeId"           =>  $store_id,
            "billingAddress"    => [
                "address1"      => $street,
                "city"          => $city,
                "region"        => $state,
                "postalCode"    => $zipCode,
                "country"       => $country
            ],
            "acsResponse"  =>   [
                "cRes" => $cres,
            ],
        ];

        return $patchPayment;
    }

    /**
     * Cancel Order
     *
     * @param Object $paymentModel
     * @param \Magento\Sales\Model\Order $currentOrder
     * @param string|null $gatewayStatus
     * @param string|null $gatewayMessage
     */
    protected function cancelOrder($paymentModel, $currentOrder, $gatewayStatus = null, $gatewayMessage = null)
    {
        $paymentModel->setData('status', 4);

        if ($gatewayStatus !== null && empty($paymentModel->getData('remote_status_or_code'))) {
            $paymentModel->setData('remote_status_or_code', $gatewayStatus);
        }
        if ($gatewayMessage !== null && empty($paymentModel->getData('remote_message'))) {
            $paymentModel->setData('remote_message', $gatewayMessage);
        }

        $this->paymentsRepository->save($paymentModel);

        // Payment Cancelled
        if ($currentOrder->canCancel()) {
            $this->helper->cancelOrder($currentOrder);
            $this->messageManager->addErrorMessage((string) __('There is an error with the payment.' .
            ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.'));
        }
        $this->helper->restoreQuote();
        $this->helper->setLloydsCookie('1');
    }
}
