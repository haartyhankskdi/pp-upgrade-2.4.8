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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class HealthCheck extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param Config $config
     * @param Data $helper
     * @param Curl $curl
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        Config $config,
        Data $helper,
        Curl $curl,
        JsonHelper $jsonHelper
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->config = $config;
        $this->helper = $helper;
        $this->curl = $curl;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Executes the health check process for the payment gateway and returns the result as a JSON response.
     *
     * @return Json JSON response containing the result of the health check. The response includes
     *              either a successful health check result or an error message if the health check fails.
     */
    public function execute(): Json
    {
        $result = $this->jsonResultFactory->create();

        try {
            $isProd = $this->getRequest()->getParam('is_prod');
            $mode = ($isProd == 1) ? 'Live' : 'Test';
            $scopeStoreId = $this->getRequest()->getParam('scope_store_id');
            $scopeStoreId = ($scopeStoreId === null || $scopeStoreId === '') ? null : (int) $scopeStoreId;
            $basicConfig = $this->config->getBasicConfigurations($mode, $scopeStoreId);
            $storeId = $basicConfig['store_id'];
            $apiKey = $basicConfig['api_key'];
            $apiSecret = $basicConfig['api_secret'];

            if (empty($storeId) || empty($apiKey) || empty($apiSecret)) {
                return $result->setData([
                    'status' => 'error',
                    'message' => 'Please fill in all required credentials for ' . ucfirst($mode) . ' mode.'
                ]);
            }

            return $result->setData(
                $this->performHealthCheck($storeId, $apiKey, $apiSecret, $isProd, $mode, $scopeStoreId)
            );

        } catch (\Exception $e) {
            $this->helper->addLog('Health check failed: ' . $e->getMessage());
            return $result->setData(['status' => 'error', 'message' => 'Health check failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Performs the actual health check API call to the Lloyds Cardnet payment gateway.
     *
     * @param string $storeId The store ID for the payment gateway.
     * @param string $apiKey The API key for the payment gateway.
     * @param string $apiSecret The API secret for the payment gateway.
     * @param bool $isProd Whether or not to perform the health check in production mode.
     * @param string $mode Mode being tested ('Live' or 'Test').
     * @param int|null $scopeStoreId Store scope being validated, or null for the default scope.
     * @return array The result of the health check. The response includes either a successful health check result or an
     *              error message if the health check fails.
     * @throws LocalizedException If the health check fails.
     */
    private function performHealthCheck(
        string $storeId,
        string $apiKey,
        string $apiSecret,
        $isProd,
        string $mode,
        ?int $scopeStoreId = null
    ): array {
        try {
            $baseUrl = $this->helper->getApiBaseUrlLBOP($mode, $scopeStoreId);

            $endpoint = $baseUrl . 'ipp/payments-gateway/v2/card-information';
            $timestamp = (int)(microtime(true) * 1000);
            $clientRequestId = $this->generateUuid();
            $payload = $this->jsonHelper->jsonEncode([
                "storeId" => $storeId,
                "paymentCard" => ["number" => '5424180279791732']
            ]);

            $messageSignature = base64_encode(hash_hmac(
                'sha256',
                $apiKey . $clientRequestId . $timestamp . $payload,
                $apiSecret,
                true
            ));

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Api-Key' => $apiKey,
                'Timestamp' => $timestamp,
                'Client-Request-Id' => $clientRequestId,
                'Message-Signature' => $messageSignature
            ];

            $this->curl->setHeaders($headers);
            $this->curl->setTimeout(30);
            $this->curl->post($endpoint, $payload);

            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($httpCode === 200 || $httpCode === 201) {
                return [
                    'status' => 'ok',
                    'message' => "You're all set!",
                    'environment' => $isProd ? 'Production' : 'Test',
                    'http_code' => $httpCode,
                    'store_id' => $storeId
                ];
            }

            $responseData = $this->jsonHelper->jsonDecode($response);
            $errorMessage = "HTTP {$httpCode}";

            if ($httpCode === 401) {
                $errorMessage = 'Authentication failed - invalid API credentials';
            } elseif ($httpCode === 403) {
                $errorMessage = 'Access forbidden - check API permissions';
            } elseif ($httpCode === 404) {
                $errorMessage = 'Endpoint not found - verify API configuration';
            } elseif ($httpCode >= 500) {
                $errorMessage = 'Server error - API temporarily unavailable';
            } elseif ($responseData && isset($responseData['error']['message'])) {
                $errorMessage .= ' - ' . $responseData['error']['message'];
            }

            throw new LocalizedException(__($errorMessage));

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'environment' => $isProd ? 'Production' : 'Test'
            ];
        }
    }

    /**
     * Generates a random UUID in the format xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
     *
     * @return string
     */
    private function generateUuid(): string
    {
        $out = bin2hex(random_bytes(18));
        $out[8] = $out[13] = $out[18] = $out[23] = "-";
        $out[14] = "4";
        $out[19] = ["8", "9", "a", "b"][random_int(0, 3)];
        return $out;
    }

    /**
     * Check if the admin user has permission to access the specific configuration section.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('AutifyDigital_LloydscardnetPayment::config');
    }
}
