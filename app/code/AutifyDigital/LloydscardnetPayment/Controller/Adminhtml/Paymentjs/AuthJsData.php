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

use AutifyDigital\LloydscardnetPayment\Helper\Data;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Model\Payment\Lcnetpaymentjs;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session\Quote as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

class AuthJsData extends Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

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
    protected $curl;

    /**
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param Data $autifyDigitalHelper
     * @param SerializerInterface $serializer
     * @param CheckoutSession $checkoutSession
     * @param PaymentTokenFactory $paymentTokenFactory
     * @param Config $config
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param DateTime $dateTime
     * @param Lcnetpaymentjs $lcnetPaymentjs
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        Data $autifyDigitalHelper,
        SerializerInterface $serializer,
        CheckoutSession $checkoutSession,
        PaymentTokenFactory $paymentTokenFactory,
        Config $config,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        DateTime $dateTime,
        Lcnetpaymentjs $lcnetPaymentjs,
        StoreManagerInterface $storeManager,
        Curl $curl,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonResultFactory;
        $this->helper = $autifyDigitalHelper;
        $this->serializer = $serializer;
        $this->checkoutSession = $checkoutSession;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->config = $config;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->dateTime = $dateTime;
        $this->lcnetPaymentjs = $lcnetPaymentjs;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * Check if admin session is valid
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('AutifyDigital_LloydscardnetPayment::payment');
    }

    /**
     * Generate client token for paymentjs
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $returnData = ['error' => false, 'message' => ''];

        try {
            $this->helper->addLog('AuthJsData: Starting execution');
            $mode = $this->config->getConfig('payment/basic/lloyds_mode');
            $config = $this->config->getBasicConfigurations($mode);
            $secretKey = $config['api_secret'];
            $apiKey = $config['api_key'];
            $storeId = $config['store_id'];
            $nonce = time() * 1000 + rand();
            $timestamp = time() * 1000;
            $payload = $this->serializer->serialize([
                "gateway" => 'IPG',
                "apiKey" => $apiKey,
                "apiSecret" => $secretKey,
                'zeroDollarAuth' => false,
                'storeId' => $storeId
            ]);

            $msg = $apiKey . $nonce . $timestamp . $payload;
            $messageSignature = base64_encode(hash_hmac('sha256', $msg, $secretKey, true));

            $url = ($mode === 'Test')
                ? 'https://cert.api.firstdata.com/paymentjs/v2/merchant/authorize-session'
                : 'https://prod.api.firstdata.com/paymentjs/v2/merchant/authorize-session';

            $this->curl->addHeader('Api-Key', $apiKey);
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Message-Signature', $messageSignature);
            $this->curl->addHeader('Nonce', (string)$nonce);
            $this->curl->addHeader('Timestamp', (string)$timestamp);
            $this->curl->setTimeout(60);

            $this->curl->post($url, $payload);

            if ($this->curl->getStatus() !== 200) {
                throw new LocalizedException(__('Payment gateway returned error: %1', $this->curl->getStatus()));
            }

            $clientToken = $this->curl->getHeaders()['client-token'] ?? $this->curl->getHeaders()['Client-Token'] ?? null;
            if (!$clientToken) {
                throw new LocalizedException(__('Client-Token header missing from response'));
            }

            try {
                $bodyData = $this->serializer->unserialize($this->curl->getBody());
                $publicKeyBase64 = $bodyData['publicKeyBase64'] ?? '';
            } catch (\Exception $e) {
                preg_match('/"publicKeyBase64":"([^"]+)"/', $this->curl->getBody(), $matches);
                $publicKeyBase64 = $matches[1] ?? '';
            }

            $this->storePaymentToken($clientToken);

            $returnData = [
                'error' => false,
                'message' => '',
                'clientToken' => $clientToken,
                'publicKeyBase64' => $publicKeyBase64
            ];

        } catch (LocalizedException $e) {
            $returnData = ['error' => true, 'message' => $e->getMessage()];
            $this->helper->addLog('AuthJsData LocalizedException: ' . $e->getMessage());
        } catch (\Exception $e) {
            $returnData = ['error' => true, 'message' => __('An error occurred. Please try again.')];
            $this->helper->addLog('AuthJsData Exception: ' . $e->getMessage());
        }

        return $resultJson->setData($returnData);
    }

    /**
     * Store Payment Client Token
     *
     * @param string $clientToken The client token to be stored.
     *
     * @return void
     */
    private function storePaymentToken(string $clientToken): void
    {
        $quote = $this->checkoutSession->getQuote();
        $customerId = $quote->getCustomerId() ?: null;
        $quoteId = $quote->getId();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('details', '%"quote_id":"' . $quoteId . '"%', 'like')
            ->create();

        $paymentTokens = $this->paymentTokenRepository->getList($searchCriteria)->getItems();

        foreach ($paymentTokens as $token) {
            $this->paymentTokenRepository->delete($token);
        }

        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setTokenDetails($this->serializer->serialize([
            'quote_id' => $quoteId,
            'client_token' => $clientToken
        ]));

        $paymentToken->setCustomerId($customerId)
            ->setPublicHash(hash('sha256', $clientToken))
            ->setWebsiteId((int)$quote->getStore()->getWebsiteId())
            ->setPaymentMethodCode($this->lcnetPaymentjs->getCode())
            ->setType('lloydscardnet')
            ->setIsActive(true)
            ->setIsVisible(true)
            ->setCreatedAt($this->dateTime->date('Y-m-d H:i:s'));

        $this->paymentTokenRepository->save($paymentToken);
    }
}
