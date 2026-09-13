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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Paymentjs;

use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session\Quote as AdminCheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;

/**
 * Class Process Payment JS Data for Admin
 */
class ProcessJsData extends Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var PaymentTokenCollectionFactory
     */
    protected $paymentTokenCollectionFactory;
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var SortOrderBuilder
     */
     protected $sortOrderBuilder;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

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
     */
    protected $helper;

    /**
     * @var string
     */
    protected $_baseUrl;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var AdminCheckoutSession
     */
    protected $adminCheckoutSession;

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
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

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
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * @param Context $context
     * @param HelperData $helperData
     * @param Config $config
     * @param AdminCheckoutSession $adminCheckoutSession
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param OrderFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
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
     * @param CookieManagerInterface $cookieManager
     * @param SortOrderBuilder $sortOrderBuilder
     * @param PaymentTokenCollectionFactory $paymentTokenCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param PaymentsRepositoryInterface $paymentsRepository
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        Config $config,
        AdminCheckoutSession $adminCheckoutSession,
        JsonFactory $resultJsonFactory,
        LayoutFactory $resultLayoutFactory,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
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
        CookieManagerInterface $cookieManager,
        SortOrderBuilder $sortOrderBuilder,
        PaymentTokenCollectionFactory $paymentTokenCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        PaymentsRepositoryInterface $paymentsRepository
    ) {
        parent::__construct($context);
        $this->helper = $helperData;
        $this->config = $config;
        $this->adminCheckoutSession = $adminCheckoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->lcPaymentsFactory = $lcPaymentsFactory;
        $this->quoteRepository = $quoteRepository;
        $this->serializer = $serializer;
        $this->_baseUrl = $this->config->getStoreUrl();
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->storeManager = $storeManager;
        $this->lcnetPaymentjs = $lcnetPaymentjs;
        $this->encryptor = $encryptor;
        $this->dateTime = $dateTime;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->cookieManager = $cookieManager;
        $this->paymentTokenCollectionFactory = $paymentTokenCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->paymentsRepository = $paymentsRepository;
    }

    /**
     * Create Csrf Validation Exception
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * Validate For Csrf
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Get Current Order from quote ID
     *
     * @return \Magento\Sales\Model\Order|null
     */
    protected function getCurrentOrder(): ?\Magento\Sales\Model\Order
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            return null;
        }
        
        $order = $this->orderRepository->get((int)$orderId);
        if ($order instanceof \Magento\Sales\Model\Order) {
            return $order;
        }
        
        return null;
    }

    /**
     * Initialize ReDirect Payment Parameters
     *
     * @param string $mode
     * @param int|string|null $storeId
     * @return array
     */
    protected function initializeReDirectPaymentParameters($mode, $storeId = null)
    {
        $config = $this->config->getBasicConfigurations($mode, $storeId);
        return $config;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $returnData = ['error' => false, 'message' => ''];
        $this->helper->addLog('Admin ProcessJsData Controller call');
        $resultJson = $this->resultJsonFactory->create();
        $currentOrder = null;

        try {
            $currentOrder = $this->getCurrentOrder();
            $storeId = $currentOrder ? $currentOrder->getStoreId() : null;
            $mode = $this->config->getConfig('payment/basic/lloyds_mode', $storeId);
            $config = $this->initializeReDirectPaymentParameters($mode, $storeId);
        } catch (\Exception $e) {
            $this->helper->addLog('Admin ProcessJsData Order Exception1: ' . $e->getMessage());
            $this->messageManager->addErrorMessage('Something went wrong');
            if ($currentOrder && $currentOrder->getId()) {
                $returnData['url'] = $this->_backendUrl->getUrl(
                    'sales/order/view',
                    ['order_id' => $currentOrder->getId()]
                );
            } else {
                $returnData['url'] = $this->_backendUrl->getUrl('sales/order');
            }
            $resultJson->setData($returnData);
            return $resultJson;
        }

        if (!$currentOrder || !$currentOrder->getIncrementId()) {
            $this->helper->addLog('Admin ProcessJsData: Order Id not found');
            $this->messageManager->addWarningMessage("Invalid payment request!");
            $returnData['url'] = $this->_backendUrl->getUrl('sales/order');
            $resultJson->setData($returnData);
            return $resultJson;
        }

        $paymentToken = $this->getPaymentToken();

        if (!$paymentToken) {
            $this->helper->addLog('Admin ProcessJsData: paymentToken not found');
            $this->messageManager->addErrorMessage('Something went wrong. Payment token is not found.');
            $returnData['url'] = $this->_backendUrl->getUrl('sales/order/view', ['order_id' => $currentOrder->getId()]);
            $resultJson->setData($returnData);
            return $resultJson;
        }

        try {
            $callArray = $this->getPayload($currentOrder, $config, $paymentToken);
            $total = number_format((float)$currentOrder->getGrandTotal(), 2, '.', '');
            if ($total <= 0) {
                $total = number_format((float)$currentOrder->getBaseGrandTotal(), 2, '.', '');
            }
            $paymentCode = $this->lcnetPaymentjs->getCode();

            $paymentModel = $this->lcPaymentsFactory->create();
            $paymentModel->setData('payment_method', $paymentCode);
            $paymentModel->setData('amount', $total);
            $paymentModel->setData('status', 1);
            $paymentModel->setData('order_id', $currentOrder->getId());
            $paymentModel->setData('order_increment_id', $currentOrder->getIncrementId());
            $paymentModel->setData('remote_reference', $callArray['merchantTransactionId']);
            $paymentModel->setData('save_card', 0);

            $this->helper->addLog($this->helper->getJsonEncode($callArray), true);
            $curlCall = $this->helper->callCurl(HelperData::PAYMENTS_API_URL, $callArray, 'POST', '', $currentOrder->getStoreId());
            $this->helper->addLog($this->helper->getJsonEncode($curlCall), true);

            if (isset($curlCall['status']) && $curlCall['status'] != 'error') {
                /** @var \stdClass $response */
                $response = $curlCall['response'];
                try {
                    $lloyds_response_payer_id = $response->orderId ?? '';

                    /** @var \Magento\Sales\Model\Order\Payment $payment */
                    $payment = $currentOrder->getPayment();
                    $payment->setTransactionId($response->ipgTransactionId);
                    $payment->setLastTransId($response->ipgTransactionId);
                    $payment->setIsTransactionClosed(false);
                    $payment->setIsTransactionPending(true);
                    $this->orderRepository->save($currentOrder);

                    $paymentModel->setData('cardnet_order_id', $lloyds_response_payer_id);
                    $this->paymentsRepository->save($paymentModel);
                } catch (\Exception $e) {
                    $this->helper->addLog('Admin ProcessJsData Ex: ' . $e->getMessage());
                    $this->messageManager->addErrorMessage('Something went wrong.');
                    $returnData['url'] =
                    $this->_backendUrl->getUrl(
                        'sales/order/view',
                        ['order_id' => $currentOrder->getId()]
                    );
                    $resultJson->setData($returnData);
                    return $resultJson;
                }

                if (isset($response->transactionStatus)) {
                    $lloyds_response_transaction_ref = $response->ipgTransactionId ?? '';

                    $approvalCode = $response->approvalCode ?? '';
                    $transactionStatus = $response->transactionStatus ?? '';

                    $last4 = (isset($response->paymentMethodDetails) &&
                    isset($response->paymentMethodDetails->paymentCard) &&
                    isset($response->paymentMethodDetails->paymentCard->last4)) ?
                    $response->paymentMethodDetails->paymentCard->last4 : '';

                    $brand = (isset($response->paymentMethodDetails) &&
                    isset($response->paymentMethodDetails->paymentCard) &&
                    isset($response->paymentMethodDetails->paymentCard->brand)) ?
                    $response->paymentMethodDetails->paymentCard->brand : '';

                    $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' .
                    $response->orderId;

                    $streetMatch = isset($response->processor->avsResponse->streetMatch) ?
                    $response->processor->avsResponse->streetMatch : '';
                    $postalCodeMatch = isset($response->processor->avsResponse->postalCodeMatch) ?
                    $response->processor->avsResponse->postalCodeMatch : '';

                    $paymentModel->setData('remote_status_or_code', $transactionStatus);
                    $paymentModel->setData('approval_code', $approvalCode);
                    $paymentModel->setData('brand', $brand);
                    $paymentModel->setData('last4', $last4);
                    $paymentModel->setData('ipgTransactionId', $lloyds_response_transaction_ref);
                    $paymentModel->setData('remote_message', $transactionMessage);
                    $paymentModel->setData('street_match', $streetMatch);
                    $paymentModel->setData('postcode_match', $postalCodeMatch);
                    if (in_array($transactionStatus, ['DECLINED', 'FAILED', 'VALIDATION_FAILED'])) {
                        $paymentModel->setData('status', 4);
                        $this->helper->addLog('Admin ProcessJsData: Transaction ' . $transactionStatus . ', setting payment status to failed');
                    }
                    $this->paymentsRepository->save($paymentModel);

                    if ($this->helper->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
                        $this->helper->addLog('Admin ProcessJsData Status: Approved');

                        $paymentModel->setData('status', 2);
                        $this->paymentsRepository->save($paymentModel);

                        $paymentAction = $this->config->getConfig('payment/lcnetpaymentjs/payment_action', $currentOrder->getStoreId());

                        if ($paymentAction === 'authorize') {

                            $currentOrder->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                            $currentOrder->setStatus('pending_payment');
                            
                            $payment->setIsTransactionPending(false);
                            $payment->setIsTransactionClosed(false);
                            $this->orderRepository->save($currentOrder);

                            $paymentModel->setData('status', 1);
                            $this->paymentsRepository->save($paymentModel);
                            
                            $this->messageManager->addSuccessMessage(__('Payment authorized successfully. Amount will be captured later.'));
                        } else {
                            $this->helper->addLog('Admin ProcessJsData: Sale transaction - processing order via helper');
                            
                            $payment->setIsTransactionPending(false);
                            $payment->setIsTransactionClosed(false);
                            $this->orderRepository->save($currentOrder);
                            $this->helper->processOrder($currentOrder, 0, 1);
                            
                            /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
                            $orderPayment = $currentOrder->getPayment();
                            $paymentMethodTitle = $orderPayment->getMethodInstance()->getTitle();
                            $this->messageManager->addSuccessMessage(__('Order processed successfully by %1.', $paymentMethodTitle));
                        }

                        $returnData['url'] = $this->_backendUrl->getUrl(
                            'sales/order/view',
                            ['order_id' => $currentOrder->getId()]
                        );

                        $resultJson->setData($returnData);
                        return $resultJson;
                    } else {
                        /*Display an Error on the page using any of the
                        following error information returned by Lloyds Cards Net */
                        $paymentModel->setData('status', 4);
                        $paymentModel->setData('remote_status_or_code', $transactionStatus ? $transactionStatus : 'ERROR');
                        $paymentModel->setData('remote_message', ($approvalCode ? $approvalCode : '') . '|' . ($transactionStatus ? $transactionStatus : '') . '|Payment not approved');
                        $this->paymentsRepository->save($paymentModel);
                        $this->messageManager->addErrorMessage("Something went wrong with the payment." .
                        " Please contact customer support.");
                        if ($currentOrder->canCancel()) {
                            $this->helper->cancelOrder($currentOrder);
                            $this->messageManager->addErrorMessage('There is an error with the payment.' .
                            ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.');
                        }
                        $returnData['url'] = $this->_backendUrl->getUrl(
                            'sales/order/view',
                            ['order_id' => $currentOrder->getId()]
                        );
                        $resultJson->setData($returnData);
                        return $resultJson;
                    }
                } else {
                    $this->helper->addLog("Something went wrong with the payment." .
                    " Please contact customer support.");
                    $paymentModel->setData('status', 4);
                    $paymentModel->setData('remote_status_or_code', 'ERROR');
                    $paymentModel->setData('remote_message', 'No transactionStatus in gateway response');
                    $this->paymentsRepository->save($paymentModel);
                    $this->messageManager->addErrorMessage("Something went wrong with the payment." .
                    " Please contact customer support.");
                    if ($currentOrder->canCancel()) {
                        $this->helper->cancelOrder($currentOrder);
                        $this->messageManager->addErrorMessage('There is an error with the payment.' .
                        ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.');
                    }
                    $returnData['url'] = $this->_backendUrl->getUrl(
                        'sales/order/view',
                        ['order_id' => $currentOrder->getId()]
                    );
                    $resultJson->setData($returnData);
                    return $resultJson;
                }
            } else {
                $this->helper->addLog("Something went wrong with the payment." .
                " Please contact customer support.");
                /** @var \stdClass|null $errorResponse */
                $errorResponse = $curlCall['response'] ?? null;
                $errTransactionStatus = $errorResponse->transactionStatus ?? '';
                $errApprovalCode = $errorResponse->approvalCode ?? '';
                $errOrderId = $errorResponse->orderId ?? '';
                $errMessage = $errApprovalCode . '|' . $errTransactionStatus . '|' . $errOrderId;

                $paymentModel->setData('status', 4);
                $paymentModel->setData('remote_status_or_code', !empty($errTransactionStatus) ? $errTransactionStatus : 'ERROR');
                $paymentModel->setData('approval_code', $errApprovalCode);
                $paymentModel->setData('remote_message', $errMessage);
                $this->paymentsRepository->save($paymentModel);

                if ($errorResponse && isset($errorResponse->errorMessage) && !empty($errorResponse->errorMessage)
                ) {
                    $this->messageManager->addErrorMessage((string)$errorResponse->errorMessage);
                }

                $this->helper->addLog('There is some error in curl response.');

                if ($currentOrder->canCancel()) {
                    $this->helper->cancelOrder($currentOrder);
                    $this->messageManager->addErrorMessage('There is an error with the payment.' .
                    ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.');
                }

                $returnData['url'] = $this->_backendUrl->getUrl(
                    'sales/order/view',
                    ['order_id' => $currentOrder->getId()]
                );
                $resultJson->setData($returnData);
                return $resultJson;
            }
        } catch (\Exception $e) {
            $returnData = [
                'error' => true,
                'message' => $e->getMessage()
            ];

            $this->helper->addLog('Admin ProcessJsData Exception: ' . $e->getMessage());
            $this->messageManager->addErrorMessage('Something went wrong.');
            if ($currentOrder && $currentOrder->getId()) {
                $returnData['url'] = $this->_backendUrl->getUrl(
                    'sales/order/view',
                    ['order_id' => $currentOrder->getId()]
                );
            } else {
                $returnData['url'] = $this->_backendUrl->getUrl('sales/order');
            }
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
        if (!$currentOrder) {
            return null;
        }
        
        $quoteId = $currentOrder->getQuoteId();

        $collection = $this->paymentTokenCollectionFactory->create();
        $paymentTokenObj = $collection->addFieldToFilter('details', ['like' => '%"quote_id":"' . $quoteId . '"%']);

        $cachedData = '';
        foreach ($paymentTokenObj as $token) {
            $details = $token->getDetails();
            if (!$details) {
                continue;
            }
            $paymentTokenDetails = $this->serializer->unserialize($details);

            if (isset($paymentTokenDetails['quote_id']) && $paymentTokenDetails['quote_id'] == $quoteId) {
                $cachedData = $token->getGatewayToken();
                break;
            }
        }

        return $cachedData;
    }

    /**
     * Get API Payload
     *
     * @param \Magento\Sales\Model\Order $currentOrder
     * @param array $config
     * @param string $paymentToken
     * @return array
     */
    private function getPayload($currentOrder, $config, $paymentToken)
    {
        $paymentAction = $this->config->getConfig('payment/lcnetpaymentjs/payment_action', $currentOrder->getStoreId());

        if ($paymentAction === 'authorize') {
            $requestType = 'PaymentTokenPreAuthTransaction';
        } else {
            $requestType = 'PaymentTokenSaleTransaction';
        }
        $transactionCode = $currentOrder->getIncrementId() . '-' . time();
        $transactionOrigin = 'PHONE';
        $total = number_format((float)$currentOrder->getGrandTotal(), 2, '.', '');
        if ($total <= 0) {
            $total = number_format((float)$currentOrder->getBaseGrandTotal(), 2, '.', '');
        }
        $ccy_code = $currentOrder->getOrderCurrencyCode();
        $currency = $this->helper->getIsoCurrencyCodeFromCurrencyCode($ccy_code);
       
        $merchantTransactionId = $this->helper->generateMerchantTransactionId();

        $comments = 'AutifyDigital LBOP Magento MOTO PaymentJS';
        $invoiceNumber = $currentOrder->getIncrementId();

        $enablePONumber = $this->config->getConfig('payment/basic/active_po_number', $currentOrder->getStoreId());
        $configPONumber = $this->config->getConfig('payment/basic/dynamic_data_po_number', $currentOrder->getStoreId());
        if ($configPONumber == null || $configPONumber == '') {
            $configPONumber = '';
        }

        $configponumberarray = explode("|", $configPONumber);
        $ponumber = '';
        if ($configponumberarray[0] !== '' && $enablePONumber == 1) {
            $keys = array_keys($configponumberarray);
            $lastKey = end($keys);

            foreach ($configponumberarray as $key => $configponumberval) {
                if ($key == $lastKey) {
                    $ponumber .= (string)$currentOrder->getData($configponumberval);
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
        
        if (!$this->helper->startsWith((string)$config['store_id'], '22')) {
            $this->helper->addLog('Invalid store ID : ' . $config['store_id']);
            $this->helper->restoreQuote();
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid storeId. Please contact support.')
            );
        }

        $callArray = [
            "requestType" => $requestType,
            "storeId" => $config['store_id'],
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
                        "region" => $shipState ,
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
                "sequence" => "SUBSEQUENT",
                "scheduled" => false,
                "initiator" => "CARDHOLDER"
            ],
        ];

        return $callArray;
    }
}
