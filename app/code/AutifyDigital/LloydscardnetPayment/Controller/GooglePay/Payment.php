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

namespace AutifyDigital\LloydscardnetPayment\Controller\GooglePay;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Framework\HTTP\Client\Curl;
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
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
use Magento\Sales\Model\Order;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Payment extends \AutifyDigital\LloydscardnetPayment\Controller\Index\AbstractAction implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var LcPaymentsFactory
     */
    protected $lcPaymentsFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var HelperData
     */
    protected $helper;
    
    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\Config
     */
    protected $configModel;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @var Json
     */
    protected $serializer;
    
    /**
     * @var Http
     */
    protected $http;
    
    /**
     * @var Curl
     */
    protected $curl;
    
    /**
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

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
     * @param Context $context
     * @param HelperData $helper
     * @param Config $configModel
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
     * @param ResultFactory $resultFactory
     * @param PageFactory $resultPageFactory
     * @param Json $json
     * @param Http $http
     * @param Curl $curl
     * @param RequestInterface $request
     * @param Filesystem $filesystem
     * @param ManagerInterface $messageManager
     * @param CookieManagerInterface $cookieManager
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        Context $context,
        HelperData $helper,
        Config $configModel,
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
        ResultFactory $resultFactory,
        PageFactory $resultPageFactory,
        Json $json,
        Http $http,
        Curl $curl,
        RequestInterface $request,
        Filesystem $filesystem,
        ManagerInterface $messageManager,
        CookieManagerInterface $cookieManager,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        SessionManagerInterface $coreSession
    ) {
        parent::__construct(
            $context,
            $helper,
            $configModel,
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
        $this->lcPaymentsFactory = $lcPaymentsFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $json;
        $this->helper = $helper;
        $this->configModel = $configModel;
        $this->storeManager = $storeManager;
        $this->http = $http;
        $this->curl = $curl;
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->messageManager = $messageManager;
        $this->cookieManager = $cookieManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTime = $dateTime;
        $this->encryptor = $encryptor;
        $this->lcnetPaymentjs = $lcnetPaymentjs;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $returnData = ['error' => false, 'message' => ''];
        $this->helper->addLog('Googlepay Controller call');
        $resultJson = $this->resultJsonFactory->create();

        try {
            $currentOrder = $this->getCurrentOrder();
            $mode = $this->configModel->getConfig('payment/basic/lloyds_mode');
            $config = $this->initializeReDirectPaymentParameters($mode);
        } catch (\Exception $e) {
            $this->helper->restoreQuote();
            $this->helper->addLog('GooglePay Order Exception: ' . $e->getMessage());
            $this->messageManager->addErrorMessage((string) __('Something went wrong'));
            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
            $resultJson->setData($returnData);
            return $resultJson;
        }
        /** @var Order $currentOrder */
        if (!$currentOrder || !$currentOrder->getIncrementId()) {
            $this->helper->restoreQuote();
            $this->helper->addLog('GooglePay: Order Id not found');
            $this->messageManager->addWarningMessage((string) __("Invalid payment request!"));
            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
            $resultJson->setData($returnData);
            return $resultJson;
        }

        try {
            $paymentToken = $this->serializer->unserialize($this->request->getParam('paymentData'));
            $callArray = $this->getPayload($currentOrder, $config, $paymentToken);
            $total = number_format((float)$currentOrder->getGrandTotal(), 2, '.', '');
            if ($total <= 0) {
                $total = number_format((float)$currentOrder->getBaseGrandTotal(), 2, '.', '');
            }

            $this->helper->addLog($this->helper->getJsonEncode($callArray), true);
            $curlCall = $this->helper->callCurl(HelperData::PAYMENTS_API_URL, $callArray, 'POST');
            $this->helper->addLog($this->helper->getJsonEncode($curlCall), true);
            
            $paymentModel = $this->lcPaymentsFactory->create();

            $enablePONumber = $this->configModel->getConfig('payment/basic/active_po_number');
            /** @var string|null $configPONumber */
            $configPONumber = $this->configModel->getConfig('payment/basic/dynamic_data_po_number');
            
            $configponumberarray = explode("|", (string) $configPONumber);
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
            
            if (isset($curlCall['status']) && $curlCall['status'] != 'error') {
                $response = $curlCall['response'];
                try {
                    $lloyds_response_payer_id = $response->orderId;
                    $paymentCode = $this->lcnetPaymentjs->getCode();

                    $paymentModel->setData('amount', $total);
                    $paymentModel->setData('payment_method', 'cardnetgooglepay');
                    $paymentModel->setData('status', 1);
                    $paymentModel->setData('order_id', $currentOrder->getId());
                    $paymentModel->setData('order_increment_id', $currentOrder->getIncrementId());
                    $paymentModel->setData('remote_reference', $callArray['merchantTransactionId']);
                    $paymentModel->setData('cardnet_order_id', $lloyds_response_payer_id);
                    $this->paymentsRepository->save($paymentModel);
                } catch (\Exception $e) {
                    $this->helper->addLog('GooglePay Ex: ' . $e->getMessage());
                    $this->helper->restoreQuote();
                    $this->messageManager->addErrorMessage('Something went wrong.');
                    $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                    $resultJson->setData($returnData);
                    return $resultJson;
                }

                if (isset($response->transactionStatus)) {
                    $lloyds_response_transaction_ref = $response->ipgTransactionId ?? null;

                    $approvalCode = $response->approvalCode ?? null;
                    ;
                    $transactionStatus = $response->transactionStatus ?? null;
                    ;

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
                    $this->paymentsRepository->save($paymentModel);

                    if ($this->helper->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
                        $this->helper->addLog('GooglePay Status: Approved');

                        $paymentModel->setData('status', 2);
                        $this->paymentsRepository->save($paymentModel);

                        $quoteId = $currentOrder->getQuoteId();
                        $currentOrder->setCanSendNewEmailFlag(true);
                        $this->orderRepository->save($currentOrder);
                        
                        // Set checkout session data to properly clear cart
                        $this->checkoutSession->setLastQuoteId($quoteId);
                        $this->checkoutSession->setLastSuccessQuoteId($quoteId);
                        $this->checkoutSession->setLastOrderId($currentOrder->getId());
                        $this->checkoutSession->setLastRealOrderId($currentOrder->getIncrementId());
                        
                        /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
                        $orderPayment = $currentOrder->getPayment();
                        $paymentMethodTitle = $orderPayment->getMethodInstance()->getTitle();
                        $this->messageManager
                        ->addSuccessMessage((string) __('Order processed successfully by %1.', $paymentMethodTitle));
                        $returnData['url'] = $this->_baseUrl . 'checkout/onepage/success';

                        $this->helper->processOrder($currentOrder);
                        $resultJson->setData($returnData);
                        return $resultJson;
                    } elseif (strpos($approvalCode, 'waiting 3dsecure') !== false && $transactionStatus === 'WAITING') {
                        $this->helper->addLog('GooglePay Status: waiting');
                        $ipgTransactionId = $response->ipgTransactionId ?? null;

                        $termURL = $acsURL = $cReq = '';
                        if (isset($response->authenticationResponse->params)) {
                            $authenticationResponse = $response->authenticationResponse->params;
                            $termURL = $authenticationResponse->termURL;
                            $acsURL = $authenticationResponse->acsURL;
                            $cReq = $authenticationResponse->cReq;
                        }

                        if (!empty($termURL) && !empty($acsURL) && !empty($cReq)) {
                            $this->helper->addLog('GooglePay Status: Custom form');
                            // Submit the 3DS challenge through the same-origin ChallengeFrame
                            // controller, which dynamically whitelists the bank ACS host in the
                            // CSP "form-action" policy. Submitting the cross-origin ACS URL
                            // directly from the page is blocked by the static CSP.
                            $returnData['form_data'] = $this->helper
                                ->buildChallengeFormData((string)$acsURL, (string)$cReq, (string)$termURL);
                            $resultJson->setData($returnData);
                            return $resultJson;
                        } elseif (isset($response->authenticationResponse->secure3dMethod) &&
                            isset($response->authenticationResponse->secure3dMethod->methodForm)
                        ) {
                            $this->helper->addLog('GooglePay Status: Iframe');
                            $methodForm = $response->authenticationResponse->secure3dMethod->methodForm;
                        
                            $this->saveThreeDSMethodDataWithOrderId($this->serializer->serialize($methodForm));
                        
                            //Iframe
                            $returnData['3dsframe'] = true;
                            $returnData['data'] = $this->encryptor->encrypt($methodForm);
                            $returnData['ipg_transaction_id'] = $this->encryptor->encrypt($lloyds_response_transaction_ref);
                            $returnData['ipg_id'] = $lloyds_response_transaction_ref;
                            $returnData['order_id'] = $currentOrder->getId();
                            $resultJson->setData($returnData);
                            return $resultJson;
                        } else {
                            $this->helper->addLog('GooglePay Status: Error');
                            $this->helper->restoreQuote();
                            if ($currentOrder->canCancel()) {
                                $this->helper->cancelOrder($currentOrder);
                                $this->messageManager->addErrorMessage((string) __(
                                    'There is an error with the payment.' .
                                    ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.'
                                ));
                            }
                            $response = $curlCall['response'];
                            $this->savePaymentData($response, $paymentModel, $currentOrder);
                            $this->paymentsRepository->save($paymentModel);
                            $this->messageManager->addErrorMessage((string) __(
                                "Something went wrong with the payment. Please contact to customer support."
                            ));
                            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                            $resultJson->setData($returnData);
                            return $resultJson;
                        }
                    } else {
                        $this->messageManager->addErrorMessage((string) __("Something went wrong with the payment." .
                        " Please contact to customer support."));
                        $this->helper->restoreQuote();
                        if ($currentOrder->canCancel()) {
                            $this->helper->cancelOrder($currentOrder);
                            $this->messageManager->addErrorMessage((string) __('There is an error with the payment.' .
                            ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.'));
                        }
                        $response = $curlCall['response'];
                        $this->savePaymentData($response, $paymentModel, $currentOrder);
                        $this->paymentsRepository->save($paymentModel);
                        $returnData['url'] = $this->_baseUrl . 'checkout/cart';
                        $resultJson->setData($returnData);
                        return $resultJson;
                    }
                } else {
                    /*Display a user friendly Error on the page using any of the
                    following error information returned by Lloyds Cards Net */
                    $this->messageManager->addErrorMessage((string) __("Something went wrong with the payment." .
                    " Please contact to customer support."));
                    $this->helper->restoreQuote();
                    if ($currentOrder->canCancel()) {
                        $this->helper->cancelOrder($currentOrder);
                        $this->messageManager->addErrorMessage((string) __('There is an error with the payment.' .
                        ' Your order with ' . $currentOrder->getIncrementId() . ' was cancelled.'));
                    }
                    $response = $curlCall['response'];
                    $this->savePaymentData($response, $paymentModel, $currentOrder);
                    $this->paymentsRepository->save($paymentModel);
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
                $response = $curlCall['response'];
                $this->savePaymentData($response, $paymentModel, $currentOrder);
                $this->paymentsRepository->save($paymentModel);
                $this->helper->addLog('GooglePay: There is some error in curl response.');
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
                'message' => $e->getMessage()
            ];

            $this->helper->addLog('GooglePay Exception: ' . $e->getMessage());

            $this->messageManager->addErrorMessage('Something went wrong.');
            $returnData['url'] = $this->_baseUrl . 'checkout/cart';
        }
        return $resultJson->setData($returnData);
    }

    /**
     * Create json response
     *
     * @param string $response
     * @return \Magento\Framework\App\Response\HttpInterface
     */
    public function jsonResponse($response = '')
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'application/json');
        $this->http->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        return $this->http->setBody(
            $this->serializer->serialize($response)
        );
    }

    /**
     * Return Payload
     *
     * @param Order $currentOrder
     * @param array $config
     * @param array $paymentToken
     * @return array
     * @throws LocalizedException
     */
    private function getPayload($currentOrder, $config, $paymentToken)
    {
        $storeId = $config['store_id'];
        if (!$this->helper->startsWith((string)$storeId, '22')) {
            $this->helper->addLog('Invalid store ID: ' . $storeId);
            $this->helper->restoreQuote();
            $this->messageManager->addErrorMessage('Invalid store ID. Contact support.');
            throw new LocalizedException(__('Invalid store ID. Contact support.'));
        }
        $total = number_format((float)$currentOrder->getGrandTotal(), 2, '.', '');
        $ccy_code = $currentOrder->getOrderCurrencyCode();
        $merchantTransactionId = $this->helper->generateMerchantTransactionId();

        $authenticationType = 'Secure3DAuthenticationRequest';
        $termURL = $config['term_url'];
        $methodNotificationURL =  $config['method_notification_url'];
        $challengeIndicator = $this->config->getConfig('payment/basic/challenge_indicator');
        $challengeWindowSize =  '01';
        $invoiceNumber = $currentOrder->getIncrementId();

        $enablePONumber = $this->configModel->getConfig('payment/basic/active_po_number');
        $configPONumber = $this->configModel->getConfig('payment/basic/dynamic_data_po_number');

        $configponumberarray = explode("|", $configPONumber);
        
        $ponumber = '';
        if ($enablePONumber == 1) {
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

        $tokenString = $paymentToken['paymentMethodData']['tokenizationData']['token'];
        $paymentTokenData = $this->serializer->unserialize($tokenString);

        $signedMessage = isset($paymentTokenData['signedMessage']) ?
            $this->serializer->unserialize($paymentTokenData['signedMessage']) : '';
            $intermediateSigningKey = isset($paymentTokenData['intermediateSigningKey']['signedKey']) ?
            $this->serializer->unserialize($paymentTokenData['intermediateSigningKey']['signedKey']) : '';

            $encryptedMessage = isset($signedMessage['encryptedMessage']) ?
            $signedMessage['encryptedMessage'] : '';
            $ephemeralPublicKey = isset($signedMessage['ephemeralPublicKey']) ?
            $signedMessage['ephemeralPublicKey'] : '';
            $tag = isset($signedMessage['tag']) ? $signedMessage['tag'] : '';

        $intermediateSignature = isset($paymentTokenData['intermediateSigningKey']['signatures']) ?
            ($paymentTokenData['intermediateSigningKey']['signatures']) : '';

        $callArray = [
                "requestType" => "WalletSaleTransaction",
                "storeId" => $storeId,
                "merchantTransactionId" => $merchantTransactionId,
                "transactionOrigin" => "ECOM",
                "transactionAmount" => [
                    "total" => $total,
                    "currency" => $currentOrder->getOrderCurrencyCode()
                ],
                "walletPaymentMethod" => [
                    "walletType" => "EncryptedGooglePayWalletPaymentMethod",
                    "encryptedGooglePay" => [
                        "data" => [
                            "encryptedMessage" => $encryptedMessage,
                            "ephemeralPublicKey" => $ephemeralPublicKey,
                            "tag" => $tag,
                        ],
                        "intermediateSigningKey" => [
                            "signedKey" => [
                                "keyValue" => $intermediateSigningKey['keyValue'],
                                "keyExpiration" => $intermediateSigningKey['keyExpiration']
                            ],
                            "signatures" => $intermediateSignature
                        ],
                        "signature" => $paymentTokenData['signature'],
                        "version" => $paymentTokenData['protocolVersion']
                    ]
                ],
                "authenticationRequest" => [
                    "authenticationType" => $authenticationType,
                    "termURL" => $termURL,
                    "methodNotificationURL" => $methodNotificationURL,
                    "challengeIndicator" => $challengeIndicator,
                    "challengeWindowSize" => $challengeWindowSize,
                    "cardHolderBrowserParams" => [
                        "browserIP" => $this->helper->getRemoteIp(),
                        "browserLanguage" => substr($this->helper->getLocale(), 0, 2),
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
                            "region" => $shipState ,
                            "postalCode" => $shipZipCode,
                            "country" => $shipCountry
                        ],
                    ],
                    "orderId" => $this->helper->getOrderIdWithSuffix($currentOrder->getIncrementId()),
                    "softDescriptor" => [
                        "dynamicMerchantName" =>$this->configModel->getConfig('general/store_information/name') ?
                        $this->configModel->getConfig('general/store_information/name') : "Store"
                    ],
                    "additionalDetails" => [
                        "comments" =>  "AutifyDigital LBOP Magento GooglePay",
                        "invoiceNumber" =>  $currentOrder->getIncrementId(),
                        'purchaseOrderNumber' => $ponumber
                    ]
                ]

            ];

        // Google returns assuranceDetails only when assuranceDetailsRequired is set on the
        // card parameters; cardHolderAuthenticated=true (CRYPTOGRAM_3DS) means the wallet
        // already authenticated the cardholder, so a separate 3DS challenge is not required.
        $assuranceDetails = $paymentToken['paymentMethodData']['info']['assuranceDetails'] ?? [];
        if (($assuranceDetails['cardHolderAuthenticated'] ?? false) === true) {
            unset($callArray['authenticationRequest']);
            $this->helper->addLog('GooglePay: cardHolderAuthenticated=true - authenticationRequest removed', true);
        }

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
            $orderId = $this->orderFactory->create()->loadByIncrementId($orderIncId)->getId();

            $quote = $this->checkoutSession->getQuote();
            $customerId = $quote->getCustomerId() ? $quote->getCustomerId() : null;

            $paymentToken = $this->paymentTokenFactory->create();
            $createdAt = $this->dateTime->date('Y-m-d H:i:s');
            $paymentCode = $this->lcnetPaymentjs->getCode();

            $publicHash = hash('sha256', $customerId . $paymentCode . $createdAt);
            $gatewayToken = 'gateway_token_' . hash('sha256', (string)$orderId);

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
                ->setType('cardnetgooglepay')
                ->setIsActive(true)
                ->setIsVisible(true)
                ->setCreatedAt($createdAt)
                ->setExpiresAt('');

            $this->paymentTokenRepository->save($paymentToken);

        } catch (\Exception $e) {
             $this->helper->addLog($e->getMessage(), true);
        }
    }

    /**
     * Save payment data
     *
     * @param object $response
     * @param object $paymentModel
     * @param \Magento\Sales\Model\Order $currentOrder
     */
    public function savePaymentData($response, $paymentModel, $currentOrder)
    {
        $approvalCode = $response->approvalCode;
        $transactionStatus = $response->transactionStatus;
        $transactionMessage = $approvalCode . '|' . $transactionStatus . '|' . $response->orderId;
        $paymentModel->setData('order_id', $currentOrder->getId());
        $paymentModel->setData('order_increment_id', $currentOrder->getIncrementId());
        $paymentModel->setData('remote_reference', $response->merchantTransactionId);
        $paymentModel->setData('cardnet_order_id', $response->orderId);
        $paymentModel->setData('remote_status_or_code', $transactionStatus);
        $paymentModel->setData('approval_code', $approvalCode);
        $paymentModel->setData('remote_message', $transactionMessage);
        $paymentModel->setData('status', 4);
        $this->paymentsRepository->save($paymentModel);
    }
}
