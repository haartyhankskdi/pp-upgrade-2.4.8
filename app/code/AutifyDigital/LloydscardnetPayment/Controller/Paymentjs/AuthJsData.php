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

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory;

class AuthJsData extends Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var PaymentTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var Lcnetpaymentjs
     */
    protected $lcnetPaymentjs;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     *
     * @var PaymentTokenCollectionFactory $paymentTokenCollectionFactory
     */
    protected $paymentTokenCollectionFactory;
    
    /**
     * AuthJsData constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param SerializerInterface $serializer
     * @param CheckoutSession $checkoutSession
     * @param PaymentTokenFactory $paymentTokenFactory
     * @param Config $config
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenCollectionFactory $paymentTokenCollectionFactory
     * @param DateTime $dateTime
     * @param Lcnetpaymentjs $lcnetPaymentjs
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Data $helper,
        SerializerInterface $serializer,
        CheckoutSession $checkoutSession,
        PaymentTokenFactory $paymentTokenFactory,
        Config $config,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenCollectionFactory $paymentTokenCollectionFactory,
        DateTime $dateTime,
        Lcnetpaymentjs $lcnetPaymentjs,
        StoreManagerInterface $storeManager,
        Curl $curl
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->serializer = $serializer;
        $this->checkoutSession = $checkoutSession;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenCollectionFactory = $paymentTokenCollectionFactory;
        $this->config = $config;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->dateTime = $dateTime;
        $this->lcnetPaymentjs = $lcnetPaymentjs;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
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
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * AuthJsData Controller Action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $returnData = [
            'error' => false,
            'message' => ''
        ];
        $this->helper->addLog('AuthJsData Controller call');
        $resultJson = $this->resultJsonFactory->create();

        try {
            $mode = $this->config->getConfig('payment/basic/lloyds_mode');
            $config = $this->config->getBasicConfigurations($mode);
            $secretKey = $config['api_secret'];
            $apiKey = $config['api_key'];
            $storeId = $config['store_id'];
            $nonce = time() * 1000 + rand();
            $timestamp = time() * 1000;

            $data = [
                "gateway" => 'IPG',
                "apiKey" => $apiKey,
                "apiSecret" => $secretKey,
                'zeroDollarAuth' => false,
                'storeId' => $storeId
            ];

            $url = ($mode == 'Test') ?
                'https://cert.api.firstdata.com/paymentjs/v2/merchant/authorize-session' :
                'https://prod.api.firstdata.com/paymentjs/v2/merchant/authorize-session';

            $jsonPayload = $this->serializer->serialize($data);
            $msg = $apiKey . $nonce . $timestamp . $jsonPayload;
            $messageSignature = base64_encode(hash_hmac('sha256', $msg, $secretKey));

            $this->curl->addHeader('Api-Key', $apiKey);
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Message-Signature', $messageSignature);
            $this->curl->addHeader('Nonce', (string)$nonce);
            $this->curl->addHeader('Timestamp', (string)$timestamp);
            
            $this->curl->post($url, $jsonPayload);
            $response = $this->curl->getBody();
            $headers = $this->curl->getHeaders();
            $statusCode = $this->curl->getStatus();

            if ($statusCode === 200) {
                $client_token = $headers['Client-Token'] ?? $headers['client-token'] ?? '';
                $responseNonce = $headers['Nonce'] ?? $headers['nonce'] ?? '';
                $publicKeyBase64 = substr($response, 20, -2);

                if ($responseNonce == $nonce) {
                    $this->storePaymentClientToken($client_token, $publicKeyBase64);
                    $returnData = [
                        'error' => false,
                        'message' => '',
                        'clientToken' => $client_token,
                        'publicKeyBase64' => $publicKeyBase64
                    ];
                } else {
                    $returnData = [
                        'error' => true,
                        'message' => 'nonce validation failed for nonce "' . $nonce . '"'
                    ];
                }
            } else {
                $returnData = [
                    'error' => true,
                    'message' => 'received HTTP ' . $statusCode . ' for nonce '. $nonce
                ];
            }
        } catch (\Exception $e) {
            $returnData = [
                'error' => true,
                'message' => $e->getMessage()
            ];
            $this->helper->addLog('PaymentjsData Exception: ' . $e->getMessage());
            $this->messageManager->addErrorMessage('Something went wrong.');
        }
        
        return $resultJson->setData($returnData);
    }

    /**
     * Stores a payment client token associated with a quote.
     *
     * @param string $clientToken The client token to be stored.
     * @param string $publicKeyBase64 The public key in base64 format.
     * @return void
     */
    private function storePaymentClientToken(string $clientToken, string $publicKeyBase64): void
    {
        $quote = $this->checkoutSession->getQuote();
        $customerId = $quote->getCustomerId() ?: null;
        
        $paymentTokenCollection = $this->paymentTokenCollectionFactory->create()
            ->addFieldToFilter('details', ['like' => '%"quote_id":"' . $quote->getId() . '"%']);
        
        foreach ($paymentTokenCollection as $token) {
            $token->delete();
        }
        
        $publicHash = hash('sha256', $clientToken . $customerId . $quote->getId() . time() . uniqid('', true));
        
        $paymentToken = $this->paymentTokenFactory->create();
        $tokenDetails = $this->serializer->serialize([
            'quote_id' => $quote->getId(),
            'client_token' => $clientToken
        ]);
        
        $paymentToken->setTokenDetails($tokenDetails)
            ->setCustomerId($customerId)
            ->setPublicHash($publicHash)
            ->setGatewayToken($publicHash) 
            ->setWebsiteId((int)$this->storeManager->getStore()->getWebsiteId())
            ->setPaymentMethodCode($this->lcnetPaymentjs->getCode())
            ->setType('lloydscardnet')
            ->setIsActive(true)
            ->setIsVisible(true)
            ->setCreatedAt($this->dateTime->date('Y-m-d H:i:s'))
            ->setExpiresAt('');
        
        $this->paymentTokenRepository->save($paymentToken);
    }
}
