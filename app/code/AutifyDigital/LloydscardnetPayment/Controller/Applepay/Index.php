<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-2025 Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Controller\Applepay;

use AutifyDigital\LloydscardnetPayment\Helper\Data;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class Index implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var JsonSerializer
     */
    protected $serializer;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param PageFactory $resultPageFactory
     * @param JsonSerializer $json
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param Config $config
     * @param Curl $curl
     * @param StoreManagerInterface $storeManagerInterface
     * @param RequestInterface $request
     * @param Filesystem $filesystem
     */
    public function __construct(
        PageFactory $resultPageFactory,
        JsonSerializer $json,
        JsonFactory $resultJsonFactory,
        Data $helper,
        Config $config,
        Curl $curl,
        StoreManagerInterface $storeManagerInterface,
        RequestInterface $request,
        Filesystem $filesystem
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $json;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->config = $config;
        $this->curl = $curl;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->request = $request;
        $this->filesystem = $filesystem;
    }

    /**
     * Execute
     */
    public function execute(): ResultInterface
    {
        try {
            $applepayCredentials = $this->config->getApplePayCredentials();

            if (empty($applepayCredentials) ||
                !isset($applepayCredentials['certificate_path'], $applepayCredentials['certificate_key'], $applepayCredentials['certificate_password'], $applepayCredentials['merchantIdentifier'])
            ) {
                throw new LocalizedException(__('Apple Pay credentials not found or incomplete.'));
            }

            /** @var Store $store */
            $store = $this->storeManagerInterface->getStore();
            $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);

            $webUrl = str_replace(['http://', 'https://'], '', trim($baseUrl, '/'));

            $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            $applepayData = [
                "merchantIdentifier" => $applepayCredentials['merchantIdentifier'],
                "domainName" => $webUrl,
                "displayName" => $this->config->getConfig('general/store_information/name') ?: "Applepay"
            ];

            $jsonData = $this->serializer->serialize($applepayData);
            $applePayValidationUrl = (string)$this->request->getParam('u');

            if (!$this->isValidAppleValidationUrl($applePayValidationUrl)) {
                $this->helper->addLog('ApplePay: rejected merchant validation URL - ' . $applePayValidationUrl);
                throw new LocalizedException(__('Invalid Apple Pay validation URL.'));
            }

            $this->curl->addHeader("Content-Type", "application/json");

            $curlOpts = [
                CURLOPT_URL => $applePayValidationUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSLCERT => $mediaPath . "lloydscardnet/" . $applepayCredentials['certificate_path'],
                CURLOPT_SSLKEY => $mediaPath . "lloydscardnet/" . $applepayCredentials['certificate_key'],
                CURLOPT_SSLKEYPASSWD => $applepayCredentials['certificate_password'],
                CURLOPT_POSTFIELDS => $jsonData
            ];

            $this->curl->setOptions($curlOpts);
            $this->curl->post($applePayValidationUrl, $jsonData);

            $response = $this->curl->getBody();

            $result = $this->resultJsonFactory->create();
            $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

            $decodedResponse = $this->serializer->unserialize($response);

            if (is_array($decodedResponse)) {
                return $result->setData($decodedResponse);
            }

            // fallback: return raw response
            return $result->setData(['response' => $response]);

        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Validate the Apple Pay merchant validation URL.
     *
     * Apple always supplies an https URL on an apple.com host (apple-pay-gateway*.apple.com).
     * Restricting to that prevents this endpoint from being abused as an SSRF / file-read
     * primitive with an attacker-controlled target.
     *
     * @param string $url
     * @return bool
     */
    private function isValidAppleValidationUrl(string $url): bool
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        return $host === 'apple.com' || substr($host, -10) === '.apple.com';
    }

    /**
     * Return json response
     *
     * @param string $message
     */
    protected function jsonResponse(string $message = ''): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        return $result->setData(['error' => $message]);
    }
}
