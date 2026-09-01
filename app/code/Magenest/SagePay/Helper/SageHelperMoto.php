<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class SageHelperMoto extends AbstractHelper
{
    private $_curlFactory;
    private $_encryptor;
    protected $_storeManager;

    /**
     * SageHelperMoto constructor.
     * @param Context $context
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_curlFactory = $curlFactory;
        $this->_encryptor = $encryptor;
        $this->_storeManager = $storeManager;
    }

    public function getVendorName()
    {
        return $this->getConfigValue('vendor_name');
    }

    public function getPiEndpointUrl($websiteId)
    {
        if ($this->getConfigValue('test', false, $websiteId)) {
            return 'https://pi-test.sagepay.com/api/v1';
        } else {
            return 'https://pi-live.sagepay.com/api/v1';
        }
    }

    public function getIntegrationKey()
    {
        return $this->getConfigValue('integration_key', true);
    }

    public function getIntegrationPassword()
    {
        return $this->getConfigValue('integration_password', true);
    }

    /**
     * @param $value
     * @param false $encrypted
     * @param null $websiteId
     * @return mixed|string
     */
    public function getConfigValue($value, $encrypted = false, $websiteId = null)
    {
        $configValue = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/' . $value,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        if ($encrypted) {
            return $this->_encryptor->decrypt($configValue);
        } else {
            return $configValue;
        }
    }

    public function getConfigByScope($value, $scope)
    {
        return $configValue = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/' . $value,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scope
        );
    }
    public function getConfig($vender)
    {
        $stores = $this->_storeManager->getStores();
        $data = [
            "integration_key" => $this->getIntegrationKey(),
            "integration_password" => $this->getIntegrationPassword()
        ];
        foreach ($stores as $store) {
            $configValue = $this->getConfigByScope("vendor_name", $store->getCode());
            if ($configValue == $vender) {
                $data["integration_key"] = $this->_encryptor->decrypt(
                    $this->getConfigByScope('integration_key', $store->getCode())
                );
                $data["integration_password"] = $this->_encryptor->decrypt(
                    $this->getConfigByScope('integration_password', $store->getCode())
                );
            }
        }
        return $data;
    }

    /**
     * @deprecated
     * @return false
     */
    public function generateMerchantKey()
    {
        $jsonBody = json_encode(["vendorName" => $this->getVendorName()]);
        $url = $this->getPiEndpointUrl() . '/merchant-session-keys';
        $result = $this->executeRequest($url, $jsonBody);
        if ($result['status'] == 201) {
            return $result["data"]->merchantSessionKey;
        } else {
            return false;
        }
    }

    /**
     * @deprecated
     *
     * @param $merchantKey
     * @param $cardName
     * @param $cardNum
     * @param $expDate
     * @param $ccv
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCardIdentifier($merchantKey, $cardName, $cardNum, $expDate, $ccv)
    {
        $url = $this->getPiEndpointUrl() . '/card-identifiers';
        $jsonBody = json_encode([
            "cardDetails" => [
                "cardholderName" => $cardName,
                "cardNumber" => $cardNum,
                "expiryDate" => $expDate,
                "securityCode" => $ccv
            ]
        ]);
        $result = $this->sendRequest($url, $jsonBody, $merchantKey);

        if ($result['status'] == 201) {
            return $result["data"];
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('get card identify error')
            );
        }
    }

    public function executeRequest($url, $body, $integration = null)
    {

        $curl = $this->_curlFactory->create();

        $curl->setConfig(
            [
                'timeout' => 20,
                'verifypeer' => false,
                'verifyhost' => 2,
                'userpwd' => $this->getIntegrationKey() . ":" . $this->getIntegrationPassword()
            ]
        );

        if ($integration) {
            $curl->setConfig(
                [
                    'timeout' => 20,
                    'verifypeer' => false,
                    'verifyhost' => 2,
                    'userpwd' => $integration["integration_key"] . ":" . $integration["integration_password"]
                ]
            );
        }

        $curl->write(
            \Zend_Http_Client::POST,
            $url,
            '1.0',
            ['Content-type: application/json'],
            $body
        );
        $data = $curl->read();

        $response_status = $curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        $data = preg_split('/^\r?$/m', $data, 2);
        $data = json_decode(trim($data[1]));

        $response = [
            "status" => $response_status,
            "data" => $data
        ];

        return $response;
    }

    private function sendRequest($url, $cardJson, $merchantKey)
    {
        $http = $this->_curlFactory->create();
        $http->setConfig(
            [
                'timeout' => 120,
                'verifypeer' => false,
                'verifyhost' => 2
            ]
        );
        $headers = [
            "Authorization: Bearer " . $merchantKey,
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ];

        $http->write(
            \Zend_Http_Client::POST,
            $url,
            '1.0',
            $headers,
            $cardJson
        );
        $rawResponse = $http->read();
        $response_status = $http->getInfo(CURLINFO_HTTP_CODE);
        $http->close();

        $data = preg_split('/^\r?$/m', $rawResponse, 2);
        $data = json_decode(trim($data[1]));

        $response = [
            "status" => $response_status,
            "data" => $data
        ];

        return $response;
    }
}
