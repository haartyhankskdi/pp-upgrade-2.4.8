<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-2025 Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure is permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Controller\Applepay;

use AutifyDigital\LloydscardnetPayment\Helper\Data;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class Payment implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * @var Json
     */
    protected Json $serializer;

    /**
     * @var Http
     */
    protected Http $http;

    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var Curl
     */
    protected Curl $curl;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManagerInterface;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * Payment constructor.
     *
     * @param PageFactory $resultPageFactory
     * @param Json $json
     * @param Http $http
     * @param Data $helper
     * @param Config $config
     * @param Curl $curl
     * @param StoreManagerInterface $storeManagerInterface
     * @param RequestInterface $request
     * @param Filesystem $filesystem
     * @param ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Json $json,
        Http $http,
        Data $helper,
        Config $config,
        Curl $curl,
        StoreManagerInterface $storeManagerInterface,
        RequestInterface $request,
        Filesystem $filesystem,
        ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $json;
        $this->http = $http;
        $this->helper = $helper;
        $this->config = $config;
        $this->curl = $curl;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->messageManager = $messageManager;
        $this->resultRawFactory = $resultRawFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface|Http
     */
    public function execute()
    {
        try {
            $currentOrder = $this->helper->getOrderSession()->getLastRealOrder();

            if (!$currentOrder || !$currentOrder->getIncrementId()) {
                $this->helper->restoreQuote();
                $this->helper->addLog('ApplePay: Order Id not found');
                return $this->jsonResponse(['status' => 'failed']);
            }

            $mode = $this->config->getConfig('payment/basic/lloyds_mode');
            $config = $this->config->getBasicConfigurations($mode);
            $applepayCredentials = $this->config->getApplePayCredentials();

            $paymentDataParam = $this->request->getParam('paymentData');
            $this->helper->addLog(is_array($paymentDataParam) ?
            $this->helper->getJsonEncode($paymentDataParam) : $paymentDataParam, true);

            $paymentToken = $this->serializer->unserialize($paymentDataParam);
            $this->helper->addLog($this->helper->getJsonEncode($paymentToken), true);

            $storeId = $config['store_id'];

            if (!$this->helper->startsWith((string)$storeId, '22')) {
                $this->helper->addLog('Invalid store ID : ' . $storeId);
                $this->helper->restoreQuote();
                return $this->jsonResponse(['status' => 'failed']);
            }

            // Billing and Shipping Addresses
            $billingAddress = $currentOrder->getBillingAddress();
            $shippingAddress = $currentOrder->getShippingAddress() ?: $billingAddress;

            $billingName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
            $shippingName = $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();
            $email = $billingAddress->getEmail() ?: $currentOrder->getCustomerEmail();

            // Billing Address details
            $street = implode(' ', $billingAddress->getStreet());
            $billingPhone = $billingAddress->getTelephone();
            $city = $billingAddress->getCity();
            $state = $billingAddress->getRegion();
            $zipCode = $billingAddress->getPostcode();
            $company = $billingAddress->getCompany();
            $country = $billingAddress->getCountryId();

            // Shipping Address details
            $shipStreet = implode(' ', $shippingAddress->getStreet());
            $shipBillingPhone = $shippingAddress->getTelephone();
            $shipCity = $shippingAddress->getCity();
            $shipState = $shippingAddress->getRegion();
            $shipZipCode = $shippingAddress->getPostcode();
            $shipCompany = $shippingAddress->getCompany();
            $shipCountry = $shippingAddress->getCountryId();

            $merchantTransactionId = $this->helper->generateMerchantTransactionId();
            $total = number_format((float)$currentOrder->getGrandTotal(), 2, '.', '');

            // PO Number configuration
            $enablePONumber = $this->config->getConfig('payment/basic/active_po_number');
            $configPONumber = $this->config->getConfig('payment/basic/dynamic_data_po_number');

            $ponumber = '';
            if ($enablePONumber == 1 && $configPONumber) {
                $configPONumberArray = explode("|", $configPONumber);
                foreach ($configPONumberArray as $field) {
                    $value = $currentOrder->getData($field);
                    if ($value !== null) {
                        $ponumber .= $value . '_';
                    }
                }
                $ponumber = substr($ponumber, 0, 49);
            }

            $paymentRequest = [
                "requestType" => "WalletSaleTransaction",
                "storeId" => $storeId,
                "merchantTransactionId" => $merchantTransactionId,
                "transactionOrigin" => "ECOM",
                "transactionAmount" => [
                    "total" => $total,
                    "currency" => $currentOrder->getOrderCurrencyCode()
                ],
                "walletPaymentMethod" => [
                    "walletType" => "EncryptedApplePayWalletPaymentMethod",
                    "encryptedApplePay" => [
                        "data" => $paymentToken['data'],
                        "header" => [
                            "ephemeralPublicKey" => $paymentToken['header']['ephemeralPublicKey'],
                            "publicKeyHash" => $paymentToken['header']['publicKeyHash'],
                            "transactionId" => $paymentToken['header']['transactionId']
                        ],
                        "signature" => $paymentToken['signature'],
                        "merchantId" => $applepayCredentials['merchantIdentifier'],
                        "version" => $paymentToken['version']
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
                    "orderId" => $this->helper->getOrderIdWithSuffix($currentOrder->getIncrementId()),
                    "softDescriptor" => [
                        "dynamicMerchantName" => $this->config->getConfig('general/store_information/name') ?: "Store"
                    ],
                    "additionalDetails" => [
                        "comments" => "AutifyDigital LBOP Magento ApplePay",
                        "invoiceNumber" => $currentOrder->getIncrementId(),
                        'purchaseOrderNumber' => $ponumber
                    ]
                ]
            ];

            $this->helper->addLog($this->helper->getJsonEncode($paymentRequest), true);

            $amount = (float)$currentOrder->getGrandTotal();
            if ($amount <= 0) {
                $amount = number_format((float)$currentOrder->getBaseGrandTotal(), 2, '.', '');
            }

            $paymentModel = $this->helper->getLCPaymentFactory();
            $paymentModel->setData('payment_method', 'applepay');
            $paymentModel->setData('amount', $amount);
            $paymentModel->setData('status', 1);
            $paymentModel->setData('order_id', $currentOrder->getId());
            $paymentModel->setData('order_increment_id', $currentOrder->getIncrementId());
            $paymentModel->save();

            $curlCall = $this->helper->callCurl(Data::PAYMENTS_API_URL, $paymentRequest, 'POST');
            $this->helper->addLog($this->helper->getJsonEncode($curlCall), true);

            $response = isset($curlCall['response']) && is_object($curlCall['response']) ?
            $curlCall['response'] : (object)[];

            if (!isset($curlCall['status']) || !isset($response->transactionStatus)) {
                $this->helper->cancelOrder($currentOrder);
                $this->helper->restoreQuote();
                return $this->jsonResponse(['status' => 'failed']);
            }

            // Safe access for undefined properties
            $approvalCode = $response->approvalCode ?? '';
            $transactionStatus = $response->transactionStatus ?? '';
            $orderId = $response->orderId ?? '';
            $lloyds_response_transaction_ref = $response->ipgTransactionId ?? '';

            $last4 = $response->paymentMethodDetails->paymentCard->last4 ?? '';
            $brand = $response->paymentMethodDetails->paymentCard->brand ?? '';
            $response_code_3dsecure = $response->secure3dResponse->responseCode3dSecure ?? '';
            $streetMatch = $response->processor->avsResponse->streetMatch ?? '';
            $postalCodeMatch = $response->processor->avsResponse->postalCodeMatch ?? '';

            $transactionMessage = "$approvalCode|$transactionStatus|" .
                $this->helper->getResponseMessage($response_code_3dsecure) . "|$orderId";

            try {
                $paymentModel->addData([
                    'remote_reference' => $merchantTransactionId,
                    'remote_status_or_code' => $transactionStatus,
                    'approval_code' => $approvalCode,
                    'response_3ds_code_message' => $response_code_3dsecure,
                    'brand' => $brand,
                    'last4' => $last4,
                    'ipgTransactionId' => $lloyds_response_transaction_ref,
                    'remote_message' => $transactionMessage,
                    'street_match' => $streetMatch,
                    'postcode_match' => $postalCodeMatch,
                    'order_increment_id' => $currentOrder->getIncrementId(),
                    'cardnet_order_id' => $orderId
                ])->save();

                if (strpos($approvalCode, 'Y:') === 0 && $transactionStatus === 'APPROVED') {
                    $paymentModel->setData('status', 2)->save();
                    
                    // Set checkout session data to properly clear cart
                    $quoteId = $currentOrder->getQuoteId();
                    $this->checkoutSession->setLastQuoteId($quoteId);
                    $this->checkoutSession->setLastSuccessQuoteId($quoteId);
                    $this->checkoutSession->setLastOrderId($currentOrder->getId());
                    $this->checkoutSession->setLastRealOrderId($currentOrder->getIncrementId());
                    
                    $this->helper->getOrderSession()->setForceOrderMailSentOnSuccess(true);
                    /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
                    $orderPayment = $currentOrder->getPayment();
                    $paymentMethodTitle = $orderPayment->getMethodInstance()->getTitle();
                    $this->messageManager->addSuccessMessage((string)__(
                        'Order processed successfully by %1.', $paymentMethodTitle
                    ));
                    $this->helper->processOrder($currentOrder);
                    return $this->jsonResponse(['status' => 'success']);
                }

                $paymentModel->setData('status', 4)->save();
                $this->helper->cancelOrder($currentOrder);
                $this->helper->restoreQuote();
                return $this->jsonResponse(['status' => 'failed']);
            } catch (\Exception $e) {
                $this->helper->addLog('ApplePay Request Ex: ' . $e->getMessage());
                $paymentModel->setData('status', 4)->save();
                $this->helper->cancelOrder($currentOrder);
                $this->helper->restoreQuote();
                return $this->jsonResponse(['status' => 'failed']);
            }
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create JSON response
     *
     * @param array|string $response
     * @return Http|ResultInterface
     */
    public function jsonResponse($response = '')
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'application/json');
        $this->http->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $result = $this->resultRawFactory->create();
        $result->setContents($this->serializer->serialize($response));

        return $result;
    }
}
