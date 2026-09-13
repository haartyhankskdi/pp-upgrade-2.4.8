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

namespace AutifyDigital\LloydscardnetPayment\Model;

use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ScopeInterface;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Configuration Class
 */
class Config
{
    /**
     * @var string
     */

    public $_liveUrl = "https://www.ipg-online.com/connect/gateway/processing"; // phpcs:ignore

    /**
     * @var string
     */

    private $_testUrl = "https://test.ipg-online.com/connect/gateway/processing"; // phpcs:ignore

    /**
     * @var ScopeConfigInterface
     *
     */
    private $scopeConfig;

    /**
     * @var DirectoryList
     * */
    protected $_directorylist; // phpcs:ignore

     /**
      * @var EncryptorInterface
      */
    private $encryptorInterface;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * construct
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptorInterface
     * @param StoreManagerInterface $storeManager
     * @param DirectoryList $directorylist
     *
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptorInterface,
        StoreManagerInterface $storeManager,
        DirectoryList $directorylist
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptorInterface = $encryptorInterface;
        $this->storeManager = $storeManager;
        $this->_directorylist = $directorylist;
    }

    /**
     * Get Config Data
     *
     * @param string $configPath
     * @param int|string|null $storeId
     *
     * @return string | bool
     */
    public function getConfig(string $configPath, int|string|null $storeId = null)
    {
        if ($storeId === null || $storeId === '') {
            $storeId = $this->storeManager->getStore()->getId();
        }
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            (int) $storeId
        );
    }

    /**
     * Decrypyt Data
     *
     * @param string $password
     * @return string
     */
    public function decrypt($password)
    {
        return $this->encryptorInterface->decrypt($password);
    }

    /**
     * Get Store URL
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreUrl()
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Get Media URL
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrl()
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get Media Path
     *
     * @return string
     */
    public function getMediaPath()
    {
        return $this->_directorylist->getPath('media');
    }

    /**
     * Get Shared Secret
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getSharedSecret(int|string|null $storeId = null)
    {
        $mode = $this->getConfig('payment/basic/lloyds_mode', $storeId);
        if ($mode == 'Live') {
            $sharedSecret = $this->getConfig('payment/autifydigital/basic/live_credential/shared_secret', $storeId);
        } else {
            $sharedSecret = $this->getConfig('payment/autifydigital/basic/test_credential/shared_secret', $storeId);
        }

        return $this->decrypt($sharedSecret);
    }

    /**
     * Get basic Configuration by Mode
     *
     * @param string|null $mode
     * @param int|string|null $store_id
     *
     * @return array
     */
    public function getBasicConfigurations(?string $mode = 'Test', int|string|null $store_id = null)
    {
        $mode = ($mode === null || $mode === '') ? 'Test' : $mode;
        $config = [];

        $mediaUrl = $this->getMediaPath();

        if ($mode == 'Live') {
            $credential_piece = 'live_credential';
            $processingUrl = $this->_liveUrl;
            $restUrl = 'https://prod.api.firstdata.com/';
        } else {
            $credential_piece = 'test_credential';
            $processingUrl = $this->_testUrl;
            $restUrl = 'https://cert.api.firstdata.com/';
        }

        $storeId = $this->getConfig('payment/autifydigital/basic/' . $credential_piece . '/store_id', $store_id);
        $decryptStoreId = $this->decrypt($storeId);

        $sharedSecret = $this->getConfig('payment/autifydigital/basic/' .
        $credential_piece . '/shared_secret', $store_id);
        $decryptSharedSecret = $this->decrypt($sharedSecret);

        $apiKey = $this->getConfig('payment/autifydigital/basic/' . $credential_piece . '/api_key', $store_id);
        $decryptApiKey = $this->decrypt($apiKey);

        $apiSecret = $this->getConfig('payment/autifydigital/basic/' . $credential_piece . '/api_secret', $store_id);
        $decryptApiSecret = $this->decrypt($apiSecret);

        $config = [
            'store_id'              => $decryptStoreId,
            'shared_secret'         => $decryptSharedSecret,
            'api_key'               => $decryptApiKey,
            'api_secret'            => $decryptApiSecret,
            'pay_mode'              => 'payonly',
            'page_option'           => 'combinedpage',
            'processing_url'        => $processingUrl,
            'rest_url'              => $restUrl
        ];

        return $config;
    }

    /**
     * Retrieve Apple Pay credentials.
     *
     * @return array An associative array containing the merchant identifier, certificate key,
     *               certificate path, decrypted certificate password, and supported networks for Apple Pay.
     */
    public function getApplePayCredentials()
    {
        return [
            'merchantIdentifier' => $this->getConfig('payment/cardnetapplepay/merchant_identifier'),
            'certificate_key' => $this->getConfig('payment/cardnetapplepay/certificate_key'),
            'certificate_path' => $this->getConfig('payment/cardnetapplepay/certificate_path'),
            'certificate_password' =>
            $this->decrypt($this->getConfig('payment/cardnetapplepay/certificate_password')),
            'supported_networks' => $this->getConfig('payment/cardnetapplepay/supported_networks'),
        ];
    }
    
    /**
     * Retrieve Google Pay credentials.
     *
     * @return array
     */
    public function getGooglePayCredentials()
    {
        return [
            'payment_mode' => $this->getConfig('payment/basic/lloyds_mode'),
            'merchant_name' => $this->getConfig('payment/cardnetgooglepay/merchant_name'),
            'merchant_id' => $this->getConfig('payment/cardnetgooglepay/merchant_id'),
            'gateway_merchant_id' => $this->getConfig('payment/cardnetgooglepay/gateway_merchant_id'),
            'supported_networks' => $this->getConfig('payment/cardnetgooglepay/supported_networks'),
        ];
    }

    /**
     * Returns the decrypted store ID for the current gateway mode.
     *
     * @return string
     */
    public function getGatewayStoreId()
    {
        $mode = $this->getConfig('payment/basic/lloyds_mode');
        if ($mode == 'Live') {
            $lbopStoreId = $this->getConfig('payment/autifydigital/basic/live_credential/store_id');
        } else {
            $lbopStoreId = $this->getConfig('payment/autifydigital/basic/test_credential/store_id');
        }
        return $this->decrypt($lbopStoreId);
    }
}
