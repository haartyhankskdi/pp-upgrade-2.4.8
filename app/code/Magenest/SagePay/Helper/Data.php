<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const GATEWAY_CURRENCY = 'payment/magenest_sagepay/require/gateway_currency';

    const FRONT = 'front';

    /**
     * @var mixed|string
     */
    protected $vendorName;

    /**
     * @var mixed|string
     */
    protected $isTest;

    /**
     * @var mixed|string
     */
    protected $integrationKey;

    /**
     * @var mixed|string
     */
    protected $integrationPassword;

    /**
     * @var mixed|string
     */
    protected $minAmount;

    /**
     * @var mixed|string
     */
    protected $maxAmount;

    /**
     * @var mixed|string
     */
    protected $vendorCode;

    /**
     * @var int|string
     */
    protected $currentStoreId;

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var CurlFactory
     */
    protected $_curlFactory;

    /**
     * @var \Magento\Store\Model\StoreResolver
     */
    protected $_storeResolver;

    /**
     * @var Logger
     */
    protected $sageLogger;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param EncryptorInterface $encryptorInterface
     * @param CurlFactory $curlFactory
     * @param \Magento\Store\Model\StoreResolver $storeResolver
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param Logger $sageLogger
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptorInterface,
        CurlFactory $curlFactory,
        \Magento\Store\Model\StoreResolver $storeResolver,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_encryptor = $encryptorInterface;
        $this->_curlFactory = $curlFactory;
        $this->_storeResolver = $storeResolver;
        $this->sessionQuote = $sessionQuote;
        parent::__construct($context);
        $this->currentStoreId = $this->_storeResolver->getCurrentStoreId();
        $this->isTest = $this->getConfigValue('test');
        $this->vendorName = $this->getConfigValue('vendor_name');
        $this->integrationKey = $this->getConfigValue('integration_key', true);
        $this->integrationPassword = $this->getConfigValue('integration_password', true);
        $this->minAmount = $this->getConfigValue('min_order_total');
        $this->maxAmount = $this->getConfigValue('max_order_total');
        $this->vendorCode = $this->getConfigValue('vendor_code');
        $this->sageLogger = $sageLogger;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param $url
     * @param $payload
     * @return array
     */
    public function sendRequest($url, $payload)
    {
        $http = $this->_curlFactory->create();

        $encoded_credential = base64_encode($this->integrationKey . ':' . $this->integrationPassword);
        $headers = [
            "Authorization: Basic " . $encoded_credential,
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ];

        $method = \Zend_Http_Client::POST;

        if (!$payload) {
            $method = \Zend_Http_Client::GET;
        }
        $http->write(
            $method,
            $url,
            '1.1',
            $headers,
            $payload
        );

        $response = $http->read();
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);

        $response = (array)json_decode($response, true);

        return $response;
    }

    /**
     * @param $value
     * @param false $encrypted
     * @return mixed|string
     */
    public function getConfigValue($value, $encrypted = false)
    {
        $configValue = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/' . $value,
            ScopeInterface::SCOPE_WEBSITE
        );
        if (isset($this->sessionQuote)) {
            $configValue = $this->scopeConfig->getValue(
                'payment/magenest_sagepay/' . $value,
                ScopeInterface::SCOPE_STORE,
                $this->sessionQuote->getStore()->getCode()
            );

        }
        if ($encrypted) {
            return $this->_encryptor->decrypt($configValue);
        } else {
            return $configValue;
        }
    }

    /**
     * @return string
     */
    public function getEndpointUrl()
    {
        if ($this->isTest) {
            return 'https://test.sagepay.com/api/v1';
        } else {
            return 'https://live.sagepay.com/api/v1';
        }
    }

    /**
     * @return string
     */
    public function getPiEndpointUrl()
    {
        if ($this->isTest) {
            return 'https://pi-test.sagepay.com/api/v1';
        } else {
            return 'https://pi-live.sagepay.com/api/v1';
        }
    }

    /**
     * @return mixed|string
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * @return mixed|string
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * @return mixed|string
     */
    public function getIntegrationKey()
    {
        return $this->integrationKey;
    }

    /**
     * @return mixed|string
     */
    public function getIntegrationPassword()
    {
        return $this->integrationPassword;
    }

    /**
     * @return mixed|string
     */
    public function getVendorCode()
    {
        return $this->vendorCode;
    }

    /**
     * @return mixed|string
     */
    public function getVendorName()
    {
        return $this->vendorName;
    }

    /**
     * @return mixed|string
     */
    public function getIsTest()
    {
        return $this->isTest;
    }

    /**
     * @param $url
     * @param $payload
     * @return array
     */
    public function sendCurlRequest($url, $payload)
    {
        $http = $this->_curlFactory->create();

        $encoded_credential = base64_encode($this->integrationKey . ':' . $this->integrationPassword);
        $headers = [
            "Authorization: Basic " . $encoded_credential,
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ];

        $method = \Zend_Http_Client::POST;

        if (!$payload) {
            $method = \Zend_Http_Client::GET;
        }
        $http->write(
            $method,
            $url,
            '1.1',
            $headers,
            $payload
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

    /**
     * @param $paRes
     * @param $md
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function submit3D($paRes, $md)
    {
        $url = $this->getPiEndpointUrl();
        $threeDurl = $url . '/transactions/' . $md . '/3d-secure';
        $jsonBody = json_encode(["paRes" => $paRes]);
        $result = $this->sendCurlRequest($threeDurl, $jsonBody);
        if ($result["status"] == 201) {
            return $result["data"];
        } else {
            $descriptionErr = isset($result["data"]->description)?$result["data"]->description:"Payment exception";
            throw new LocalizedException(__($descriptionErr));
        }
    }

    public function submit3Dv2($creq, $threeDSSessionData)
    {
        $url = $this->getPiEndpointUrl();
        $threeDurl = $url . '/transactions/' . $threeDSSessionData . '/3d-secure-challenge';
        $jsonBody = json_encode(["cRes" => $creq]);
        $result = $this->sendCurlRequest($threeDurl, $jsonBody);
        if ($result["status"] == 201) {
            return $result["data"];
        } else {
            $descriptionErr = isset($result["data"]->description)?$result["data"]->description:"Payment exception";
            throw new LocalizedException(__($descriptionErr));
        }
    }

    /**
     * @var \Exception $e
     */
    public function debugException($e)
    {
        $this->sageLogger->debug($e->getFile().":".$e->getLine().":".$e->getMessage());
    }

    /**
     * @param $storeId
     * @return mixed|string
     */
    public function getGatewayCurrency($storeId)
    {
        return $this->scopeConfig->getValue(
            self::GATEWAY_CURRENCY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * @param $order
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrency($order)
    {
        $baseCurrency  = $this->getBaseCurrencyCode();
        if ($order->getIncrementId()) {
            $orderCurrency = $order->getOrderCurrency()->getCurrencyCode();
        } else {
            $orderCurrency = $order->getQuoteCurrencyCode();
        }
        return $this->getCurrencyCode($baseCurrency, $orderCurrency);
    }

    /**
     * @param $order
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function convertAmount($order)
    {
        $storeId = $order->getStoreId();
        $gateway_currency = $this->getGatewayCurrency($storeId);
        if ($gateway_currency == self::FRONT) {
            $amount = $order->getGrandTotal();
        } else {
            $amount = $order->getBaseGrandTotal();
        }
        return $amount;
    }

    /**
     * @param $baseCurrencyCode
     * @param $currentCurrencyCode
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode($baseCurrencyCode, $currentCurrencyCode)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $gateway_currency = $this->getGatewayCurrency($storeId);
        $currencyCode     = $baseCurrencyCode;
        if ($gateway_currency == self::FRONT) {
            $currencyCode = $currentCurrencyCode;
        }
        return $currencyCode;
    }

    /**
     * @param $order
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPayAmount($order) {
        $storeId = $this->_storeManager->getStore()->getId();
        $gateway_currency = $this->getGatewayCurrency($storeId);
        if ($gateway_currency == self::FRONT) {
            $amount = $order->getGrandTotal();
        } else {
            $amount = $order->getBaseGrandTotal();
        }
        return number_format($amount, 2, '.', '');
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRefundAmount(\Magento\Sales\Model\Order\Creditmemo $creditmemo) {
        $storeId = $creditmemo->getStoreId();
        $gateway_currency = $this->getGatewayCurrency($storeId);
        if ($gateway_currency == self::FRONT) {
            $amount = $creditmemo->getGrandTotal();
        } else {
            $amount = $creditmemo->getBaseGrandTotal();
        }
        return number_format($amount, 2, '.', '');
    }
}
