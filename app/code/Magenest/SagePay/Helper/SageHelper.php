<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Helper;

use Magenest\SagePay\Helper\Subscription as SubsHelper;
use Magenest\SagepayLib\Classes\Constants;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;
use Magenest\SagepayLib\Classes\SagepayApiException;
use Magenest\SagepayLib\Classes\SagepayUtil;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;

class SageHelper extends AbstractHelper
{

    const SECURE_STATUS_APPLY = "Authenticated";
    const SECURE_STATUS_NOT_APPLY = "NotAuthenticated";
    const SECURE_STATUS_NOT_ENROLL = "CardNotEnrolled";
    const SECURE_STATUS_ISSUER_NOT_ENROLL = "IssuerNotEnrolled";

    const SAGE_TRANSACTION_ID = "transaction_id";

    const SAGE_PAY_TYPE_AUTHORIZE = "Deferred";
    const SAGE_PAY_TYPE_CAPTURE = "Payment";
    const SAGE_PAY_TYPE_REPEAT = "Repeat";
    const SAGE_PAY_TYPE_REFUND = "Refund";
    const SAGE_PAY_TYPE_RELEASE = "Release";
    const SAGE_PAY_TYPE_VOID = "Void";

    const SAGE_PAY_TYPE_INSTRUCTION_VOID = "void";
    const SAGE_PAY_TYPE_INSTRUCTION_ABORT = "abort";
    const SAGE_PAY_TYPE_INSTRUCTION_RELEASE = "release";

    const SAGEPAY_SHARED_REFUND_TRANSACTION_TEST = 'https://test.sagepay.com/gateway/service/refund.vsp';
    const SAGEPAY_SHARED_REFUND_TRANSACTION_LIVE = 'https://live.sagepay.com/gateway/service/refund.vsp';
    const SAGEPAY_SHARED_RELEASE_TRANSACTION_TEST = 'https://test.sagepay.com/gateway/service/release.vsp';
    const SAGEPAY_SHARED_RELEASE_TRANSACTION_LIVE = 'https://live.sagepay.com/gateway/service/release.vsp';
    const SAGEPAY_SHARED_VOID_TRANSACTION_TEST = 'https://test.sagepay.com/gateway/service/void.vsp';
    const SAGEPAY_SHARED_VOID_TRANSACTION_LIVE = 'https://live.sagepay.com/gateway/service/void.vsp';
    const SAGEPAY_SHARED_ABORT_TRANSACTION_TEST = 'https://test.sagepay.com/gateway/service/abort.vsp';
    const SAGEPAY_SHARED_ABORT_TRANSACTION_LIVE = 'https://live.sagepay.com/gateway/service/abort.vsp';

    const DEFERRED_AWAITING_RELEASE = 14;
    const SUCCESSFULLY_AUTHORISED = 16;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $_curlFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magenest\SagePay\Model\CardFactory
     */
    protected $cardFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var
     */
    protected $encryptor;

    /**
     * @var Logger
     */
    protected $sageLogger;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;

    /**
     * @var Data
     */
    protected $data;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    /**
     * @var Subscription
     */
    private $httpHeader;
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteIp;
    /**
     * @var Json
     */
    private $json;

    /**
     * SageHelper constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param EncryptorInterface $encryptor
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magenest\SagePay\Model\CardFactory $cardFactory
     * @param Logger $sageLogger
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param Data $data
     * @param \Magento\Framework\HTTP\Header $httpHeader
     * @param Json $json
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteIp
     * @param \Magento\Framework\App\RequestInterface $request
     * @param Context $context
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magenest\SagePay\Model\CardFactory $cardFactory,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magenest\SagePay\Helper\Data $data,
        \Magento\Framework\HTTP\Header $httpHeader,
        Json $json,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteIp,
        \Magento\Framework\App\RequestInterface $request,
        Context $context
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->_encryptor = $encryptor;
        $this->_curlFactory = $curlFactory;
        $this->storeManager = $storeManager;
        $this->cardFactory = $cardFactory;
        $this->customerSession = $customerSession;
        $this->sageLogger = $sageLogger;
        $this->sessionQuote = $sessionQuote;
        $this->data = $data;
        $this->httpHeader = $httpHeader;
        $this->json = $json;
        $this->remoteIp = $remoteIp;
        $this->request = $request;
    }

    /**
     * @param array $subsPlanData
     * @param array $response
     * @param Order $order
     * @param float $amount
     * @param string $currencyCode
     * @return array
     */
    public function setSubscriptionData($subsPlanData, $response, $order, $amount, $currencyCode)
    {
        /** @var \Magenest\SagePay\Model\Profile $profileModel */
        return [
            'transaction_id' => $response['transactionId'],
            'order_id' => $order->getIncrementId(),
            'customer_id' => $order->getCustomerId(),
            'status' => SubsHelper::SUBS_STAT_ACTIVE_CODE,
            'amount' => $amount,
            'total_cycles' => $subsPlanData['total_cycles'],
            'currency' => $currencyCode,
            'frequency' => $subsPlanData['frequency'],
            'remaining_cycles' => --$subsPlanData['total_cycles'],
            'start_date' => date('Y-m-d'),
            'last_billed' => date('Y-m-d'),
            'next_billing' => date('Y-m-d', strtotime("+ " . $subsPlanData['frequency'])),
            'sequence_order_ids' => ""
        ];
    }

    /**
     * @param $api
     * @param $quote
     * @return mixed
     */
    public function handleQuoteDetailInformation($api, $quote)
    {
        $vendorTxCode = $api->getData();
        $vendorTxCode = $vendorTxCode['VendorTxCode'] ?? '';
        $quote->setPaymentMethod('magenest_sagepay_form');
        $quote->getPayment()->setAdditionalInformation("vendor_tx_code", $vendorTxCode);
        $quote->getPayment()->importData(['method' => 'magenest_sagepay_form']);
        $quote->setIsActive(false);
        return $quote;
    }

    /**
     * @param $quote
     * @param $guestEmail
     * @param $sageConfig
     * @return false|\Magenest\SagepayLib\Classes\SagepayAbstractApi
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function handleSageApi($quote, $guestEmail, $sageConfig)
    {
        $quoteDetails = $this->getPaymentDetail($quote, $guestEmail);
        $sageApi = new SagepayAPI($this->data, $sageConfig, Constants::SAGEPAY_FORM);
        return $sageApi->buildApi($quote, $quoteDetails);
    }

    public function getPiEndpointUrl()
    {
        if ($this->getIsSandbox()) {
            return 'https://pi-test.sagepay.com/api/v1';
        } else {
            return 'https://pi-live.sagepay.com/api/v1';
        }
    }

    public function getIsSandbox()
    {
        return $this->getConfigValue('test');
    }

    public function getConfigValue($value, $encrypted = false, $order = null)
    {
        $configValue = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/' . $value,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($order) {
            $configValue = $this->scopeConfig->getValue(
                'payment/magenest_sagepay/' . $value,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $order->getStore()->getCode()
            );
        }
        if ($encrypted) {
            return $this->_encryptor->decrypt($configValue);
        } else {
            return $configValue;
        }
    }

    /**
     * @return mixed|string
     */
    public function getCanSave()
    {
        return $this->getConfigValue('can_save_card');
    }

    /**
     * @param $debugData
     */
    public function debug($debugData)
    {
        $this->sageLogger->debug(var_export($debugData, true));
    }

    public function isDebugMode()
    {
        return $this->getConfigValue('debug');
    }

    /**
     * @param $transId
     * @return string
     */
    public function buildInstructionUrl($transId)
    {
        return $this->getPiEndpointUrl() . '/transactions/' . $transId . "/instructions";
    }

    /**
     * @return string
     */
    public function getEndpointUrl()
    {
        if ($this->getIsSandbox()) {
            return 'https://test.sagepay.com/api/v1';
        } else {
            return 'https://live.sagepay.com/api/v1';
        }
    }

    /**
     * @return mixed|string
     */
    public function isGiftAid()
    {
        return $this->getConfigValue('gift_aid');
    }

    /**
     * @return string|string[]|null
     */
    public function getInstructions()
    {
        return preg_replace('/\s+|\n+|\r/', ' ', $this->getConfigValue('instructions'));
    }

    /**
     * @return mixed|string
     */
    public function get3DStatusAllow()
    {
        $data = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/additional_config/apply_3d_allow',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$data) {
            $data = "Authenticated,NotChecked,NotAuthenticated,Error,CardNotEnrolled,".
                "IssuerNotEnrolled,MalformedOrInvalid,AttemptOnly,Incomplete";
        }
        return $data;
    }

    /**
     * @return mixed|string
     */
    public function useDropIn()
    {
        return $this->getConfigValue('use_dropin');
    }

    /**
     * @return mixed|string
     */
    public function getDropInMode()
    {
        return $this->getConfigValue('dropin_mode');
    }

    /**
     * @param $order
     * @param $instructionType
     * @param int $amount
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildInstructionQuery($order, $instructionType, $amount = 0)
    {
        $currencyCode = $this->data->getCurrency($order);
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($this->data->getPayAmount($order) * $multiply, 2);
        $query = '{' .
            '"instructionType": "' . $instructionType . '"';

        if ($instructionType == self::SAGE_PAY_TYPE_INSTRUCTION_RELEASE) {
            $query .= ',"amount": ' . $amount;
        }
        $query .= '}';

        return $query;
    }

    /**
     * @param $order
     * @param $sessionKey
     * @param $cardId
     * @param $type
     * @param null $save
     * @param null $reusable
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildPaymentQuery($order, $sessionKey, $cardId, $type, $save = null, $reusable = null)
    {
        return $this->handlePayloadData($order, $sessionKey, $cardId, $type, false, $save, $reusable);
    }

    /**
     * @param $order
     * @param $sessionKey
     * @param $cardId
     * @param $type
     * @param $isMoto
     * @param null $save
     * @param null $reusable
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function handlePayloadData($order, $sessionKey, $cardId, $type, $isMoto, $save = null, $reusable = null)
    {
        $ignoreAddressCheck = $this->getConfigValue('ignore_address_check');
        $testMode = $this->getIsSandbox();
        if ($ignoreAddressCheck && $testMode) {
            $address1 = "88";
            $address2 = "88";
            $postCode = "412";
        } else {
            $address1 = $order->getBillingAddress()->getStreetLine(1);
            $address2 = $order->getBillingAddress()->getStreetLine(2);
            $postCode = $order->getBillingAddress()->getPostcode();
        }
        $save = ($save == true) ? 'true' : 'false';
        $reusable = ($reusable == true) ? 'true' : 'false';
        $currencyCode = $this->data->getCurrency($order);
        if (!$isMoto) {
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();
            $giftAid = ($payment->getAdditionalInformation('gift_aid')) ? "true" : "false";
        }
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($this->data->getPayAmount($order) * $multiply, 2);
        $payload = '{' .
            '"transactionType": "' . $type . '",' .
            '"paymentMethod": {' .
            '    "card": {'.
            '        "merchantSessionKey": "' . $sessionKey . '",';
        if (!$isMoto) {
            $payload .=
            '        "cardIdentifier": "' . $cardId . '",' .
            '        "save": "' . $save . '",' .
            '        "reusable": "' . $reusable . '"';
        } else {
            $payload .=
            '        "cardIdentifier": "' . $cardId . '"';
        }
        $payload .= '    }' .
            '},' .
            '"vendorTxCode": "' . $this->generateVendorTxCode($order->getIncrementId(), $type) . '",' .
            '"amount": ' . $amount . ',' .
            '"currency": "' . $currencyCode . '",' .
            '"description": "' . $this->getPaymentDescription($order, $isMoto) . '",' .
            '"apply3DSecure": "' . $this->getIsApply3DSecure($isMoto) . '",' .
            '"applyAvsCvcCheck": "' . $this->getIsApplyCvcCheck() . '",' .
            '"customerFirstName": "' . $order->getBillingAddress()->getFirstname() . '",' .
            '"customerLastName": "' . $order->getBillingAddress()->getLastname() . '",' .
            '"customerEmail": "' . $order->getBillingAddress()->getEmail() . '",' .
            '"customerPhone": "' . $order->getBillingAddress()->getTelephone() . '",' .
            '"billingAddress": {' .
            '    "address1": "' . $address1 . '",' .
            '    "address2": "' . $address2 . '",' .
            '    "postalCode": "' . $postCode . '",';
        if ($order->getBillingAddress()->getCountryId() == 'US') {
            $payload .= '    "state": "' . $order->getBillingAddress()->getRegionCode() . '",';
        }

        $payload .= '    "city": "' . $order->getBillingAddress()->getCity() . '",' .
            '    "country": "' . $order->getBillingAddress()->getCountryId() . '"';
        $payload .= '},';
        $shipping = $order->getShippingAddress();
        if (!!$shipping) {
            $payload .= $this->handleShippingData($shipping);
        }
        if (!$isMoto) {
            $payload .= '"giftAid": "' . $giftAid . '",';
            $payload .= '"entryMethod": "Ecommerce",';
        } else {
            $payload .= '"entryMethod": "TelephoneOrder",';
        }
        $payload .= '"referrerId": "1BC70868-12A8-1383-A2FB-D7A0205DE97B"';
        if ($this->getIsApplyPi3DSecureV2()) {
            $payload .= ',"strongCustomerAuthentication":'.$this->getStrongCustomerAuthentication();
            if ($save == 'true') {
                $payload .= ',"credentialType":' . $this->getCredentialType('save');
            } elseif ($reusable == 'true') {
                $payload .= ',"credentialType":' . $this->getCredentialType('reusable');
            }
        }
        $payload .= '}';

        return $payload;
    }

    public function getVendorCode()
    {
        return $this->getConfigValue('vendor_code');
    }

    public function getIsApply3DSecure($disable3DSecure = false)
    {
        $config3DSecure = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/apply_3d_secure',
            ScopeInterface::SCOPE_STORE
        );
        if ($disable3DSecure) {
            return "Disable";
        } else {
            return $config3DSecure;
        }
    }

    /**
     * @param mixed $websiteId
     * @return mixed
     */
    public function getIsApply3DSecureV2($websiteId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay/enable_3ds2',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param mixed $websiteId
     * @return mixed
     */
    public function getIsApplyPi3DSecureV2($websiteId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay/enable_pi_3ds2',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param mixed $storeId
     * @return mixed
     */
    public function getIsApplyCvcCheck($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay/apply_cvc_check',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $order
     * @param $transId
     * @param $amount
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildRefundQuery($order, $transId, $amount)
    {
        $currencyCode = $this->data->getCurrency($order);
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($amount * $multiply, 2);
        $payload = '{' .
            '"transactionType": "Refund",' .
            '"referenceTransactionId": "' . $transId . '",' .
            '"amount": ' . $amount . ',' .
            '"vendorTxCode": "' . $this->generateVendorTxCode($order->getIncrementId(), "Refund", $order) . '",' .
            '"description": "' . $this->getRefundDescription($order) . '"';
        $payload .= '}';

        return $payload;
    }

    /**
     * @param $refTransId
     * @param $order
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildRepeatQuery($refTransId, $order)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $giftAid = ($payment->getAdditionalInformation('gift_aid')) ? "true" : "false";
        $amount = $this->data->getPayAmount($order);
        $currencyCode = $this->data->getCurrency($order);
        $multiply = 100;
        if ($this->isZeroDecimal($currencyCode)) {
            $multiply = 1;
        }
        $amount = round($amount * $multiply, 2);
        $payload = '{' .
            '"transactionType": "Repeat",' .
            '"referenceTransactionId":"' . $refTransId . '",' .
            '"vendorTxCode": "' . $this->generateVendorTxCode($order->getIncrementId(), "Repeat", $order) . '",' .
            '"amount": ' . $amount . ',' .
            '"currency": "' . $currencyCode . '",' .
            '"description": "' . $this->getRepeatDescription($order) . '",';
        $shipping = $order->getShippingAddress();
        if (!!$shipping) {
            $payload .= $this->handleShippingData($shipping);
        }
        $payload .= '"giftAid": "' . $giftAid . '",';
        $payload .= '"referrerId": "1BC70868-12A8-1383-A2FB-D7A0205DE97B"';
        $payload .= '}';

        return $payload;
    }

    /**
     * @param $shipping
     * @return string
     */
    private function handleShippingData($shipping)
    {
        $payload =
            '"shippingDetails": {' .
            '    "recipientFirstName": "' . $shipping->getFirstname() . '",' .
            '    "recipientLastName": "' . $shipping->getLastname() . '",' .
            '    "shippingAddress1": "' . $shipping->getStreetLine(1) . '",' .
            '    "shippingAddress2": "' . $shipping->getStreetLine(2) . '",';
        $payload .= '    "shippingPostalCode": "' . $shipping->getPostcode() . '",';
        if ($shipping->getCountryId() == 'US') {
            $payload .= '    "shippingState": "' . $shipping->getRegionCode() . '",';
        }
        $payload .= '    "shippingCity": "' . $shipping->getCity() . '",' .
            '    "shippingCountry": "' . $shipping->getCountryId() . '"';
        $payload .= '},';
        return $payload;
    }

    /**
     * @param $secureCode
     * @return string
     */
    public function buildLinkSecureCodeQuery($secureCode)
    {
        $payload = '{' .
            '"securityCode": "' . $secureCode . '"' .
            '}';

        return $payload;
    }

    /**
     * @param $url
     * @param $payload
     * @param null $order
     * @return array
     */
    public function sendRequest($url, $payload, $order = null)
    {
        $integrationKey = $this->getConfigValue('integration_key', true, $order);
        $integrationPass = $this->getConfigValue('integration_password', true, $order);
        $http = $this->_curlFactory->create();
        $encoded_credential = base64_encode($integrationKey . ':' . $integrationPass);
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
     * @param $url
     * @param $payload
     * @return false|mixed|string[]
     */
    public function sendFormRequest($url, $payload)
    {
        $http = $this->_curlFactory->create();

        $method = \Zend_Http_Client::POST;

        if (!$payload) {
            $method = \Zend_Http_Client::GET;
        }
        $http->write(
            $method,
            $url,
            '1.1',
            //            $headers,
            ['Content-Type: application/x-www-form-urlencoded'],
            $payload
        );
        $response = $http->read();

        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);

        $response = explode("\r\n", $response);
        if ($response[0] != "VPSProtocol=3.00" &&
            $response[0] != "VPSProtocol=3" &&
            $response[0] != "VPSProtocol=4.00" &&
            $response[0] != "VPSProtocol=4"
        ) {
            $xml = simplexml_load_string($response[0], "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $response = json_decode($json, true);
        }

        return $response;
    }

    /**
     * @param Order $order
     */
    public function getPaymentDescription($order, $isMoto = false)
    {
        $storeName = $order->getStore()->getName();
        if (!$isMoto) {
            return "Order " . $order->getIncrementId() . " at " . $storeName;
        } else {
            return "MOTO transaction. Order " . $order->getIncrementId() . " at " . $storeName;
        }
    }

    /**
     * @param $order
     * @return string
     */
    public function getRefundDescription($order)
    {
        $storeName = $order->getStore()->getName();

        return "Refund Order " . $order->getIncrementId() . " at " . $storeName;
    }

    /**
     * @param $order
     * @return string
     */
    public function getRepeatDescription($order)
    {
        $storeName = $order->getStore()->getName();

        return "Recurring Order " . $order->getIncrementId() . " at " . $storeName;
    }

    /**
     * @param $currency
     * @return bool
     */
    public function isZeroDecimal($currency)
    {
        return in_array(strtolower($currency), [
            'bif',
            'djf',
            'jpy',
            'krw',
            'pyg',
            'vnd',
            'xaf',
            'xpf',
            'clp',
            'gnf',
            'kmf',
            'mga',
            'rwf',
            'vuv',
            'xof'
        ]);
    }

    /**
     * @param string $order_id
     * @param string $type
     * @param null $order
     * @return false|string
     */
    public function generateVendorTxCode($order_id = "", $type = "", $order = null)
    {
        $parts = [];
        $parts[] = $this->getConfigValue("vendor_name", false, $order);
        if (trim($type) != "") {
            $parts[] = strtoupper($type);
        }
        if (trim($order_id) != "") {
            $parts[] = $order_id;
        }
        $parts[] = rand(0, 1000000000);
        $vendorTxCode = implode('-', $parts);
        return substr($vendorTxCode, 0, 40);
    }

    /**
     * @param mixed $storeId
     * @return mixed
     */
    public function activeMoto($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay/active_moto',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $order
     * @param $sessionKey
     * @param $cardId
     * @param $type
     * @param null $save
     * @param null $reusable
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function buildMotoPaymentQuery($order, $sessionKey, $cardId, $type)
    {
        return $this->handlePayloadData($order, $sessionKey, $cardId, $type, true);
    }

    /**
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStrongCustomerAuthentication()
    {
        $userAgent = $this->httpHeader->getHttpUserAgent();
        $remote_ip = $this->remoteIp->getRemoteAddress();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $challengeWindowSize = $this->scopeConfig->getValue('payment/magenest_sagepay/challenge_window_size');
        $transType = $this->scopeConfig->getValue('payment/magenest_sagepay/trans_type');
        $payload = [
            'notificationURL' => $baseUrl.'sagepay/checkout/redirectBack',
            'browserIP' => $remote_ip,
            'browserAcceptHeader' => 'text/html, application/json',
            'browserJavascriptEnabled' => false,
            'browserLanguage' => 'en-GB',
            'browserUserAgent' => $userAgent,
            'challengeWindowSize' => $challengeWindowSize,
            'transType' => $transType,
        ];
        return $this->json->serialize($payload);
    }

    /**
     * @return false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCredentialType($type)
    {
        switch ($type) {
            case 'save':
                $cofUsage = 'First';
                $initiatedType = 'CIT';
                break;
            case 'reusable';
            default:
                $cofUsage = 'Subsequent';
                $initiatedType = 'MIT';
                break;
        }
        $payload = [
            'cofUsage' => $cofUsage,
            'initiatedType' => $initiatedType,
            'mitType' => 'Unscheduled'
        ];
        return $this->json->serialize($payload);
    }
    /**
     * @param $customerId
     * @param \stdClass $card
     * @throws \Exception
     */
    public function saveCard($customerId, $card)
    {
        if (is_array($card)) {
            $cardType = $card['cardType'];
            $last4 = $card['lastFourDigits'];
            $expireDate = $card['expiryDate'];
            $cardId = $card['cardIdentifier'];
            $reusable = $card['reusable'];
        } else {
            $cardType = $card->cardType;
            $last4 = $card->lastFourDigits;
            $expireDate = $card->expiryDate;
            $cardId = $card->cardIdentifier;
            $reusable = $card->reusable;
        }

        if ($reusable) {
            $cardModel = $this->cardFactory->create();
            $data = [
                'customer_id' => $customerId,
                'card_id' => $cardId,
                'card_type' => $cardType,
                'last_4' => (string)$last4,
                'expire_date' => (string)$expireDate
            ];
            $cardModel->addData($data)->save();
        }
    }

    /**
     * @return string
     */
    public function getApiEnv()
    {
        $isTest = $this->getConfigValue("test");
        if ($isTest == "1") {
            return "test";
        } else {
            return "live";
        }
    }

    /**
     * @param $mode
     * @return int
     */
    public function convertApi3DsCcv($mode)
    {
        if ($mode == "UseMSPSetting") {
            return 0;
        }
        if ($mode == "Force") {
            return 1;
        }
        if ($mode == "Disable") {
            return 2;
        }
        if ($mode == "ForceIgnoringRules") {
            return 3;
        }
        return 0;
    }

    public function getEncryptedPassword()
    {
        $mode = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/test',
            ScopeInterface::SCOPE_WEBSITE
        );
        if ($mode == 1) {
            return $this->getTestEncryptedPassword();
        } else {
            return $this->getLiveEncryptedPassword();
        }
    }

    /**
     * @param mixed $websiteId
     * @return mixed
     */
    public function getTestEncryptedPassword($websiteId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay_form/test_encrypted_password',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param mixed $websiteId
     * @return mixed
     */
    public function getLiveEncryptedPassword($websiteId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay_form/live_encrypted_password',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * @param mixed $storeId
     * @return string
     */
    public function getSageFormPaymentAction($storeId = null)
    {
        $paymentAction = $this->scopeConfig->getValue(
            'payment/magenest_sagepay_form/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($paymentAction == "authorize_capture") {
            return "PAYMENT";
        }
        if ($paymentAction == "authorize") {
            return "DEFERRED";
        }
        return "PAYMENT";
    }

    /**
     * @param mixed $storeId
     * @return string
     */
    public function getSageServerPaymentAction($storeId = null)
    {
        $paymentAction = $this->scopeConfig->getValue(
            'payment/magenest_sagepay_server/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($paymentAction == "authorize_capture") {
            return "PAYMENT";
        }
        if ($paymentAction == "authorize") {
            return "DEFERRED";
        }
        return "PAYMENT";
    }

    /**
     * @param mixed $storeId
     * @return string
     */
    public function getSageDirectAction($storeId = null)
    {
        $paymentAction = $this->scopeConfig->getValue(
            'payment/magenest_sagepay_direct/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($paymentAction == "authorize_capture") {
            return "PAYMENT";
        }
        if ($paymentAction == "authorize") {
            return "DEFERRED";
        }
        return "PAYMENT";
    }

    /**
     * @param mixed $storeId
     * @return string
     */
    public function getSageDirectPaypalAction($storeId = null)
    {
        $paymentAction = $this->scopeConfig->getValue(
            'payment/magenest_sagepay_paypal/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($paymentAction == "authorize_capture") {
            return "PAYMENT";
        }
        if ($paymentAction == "authorize") {
            return "DEFERRED";
        }
        return "PAYMENT";
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function getPaymentDetail($quote, $guestEmail)
    {
        $quoteBillingAddress = $quote->getBillingAddress();
        $quoteShippingAddress = $quote->getShippingAddress() ?? $quoteBillingAddress;
        $arr = [];
        if (!$this->customerSession->isLoggedIn()) {
            $arr['customerEmail'] = $guestEmail;
        } else {
            $arr['customerEmail'] = $quote->getCustomerEmail();
        }
        $arr['BillingFirstnames'] = $quoteBillingAddress->getFirstname();
        $arr['BillingSurname'] = $quoteBillingAddress->getLastname();
        $arr['BillingAddress1'] = $quoteBillingAddress->getStreetLine(1);
        $arr['BillingAddress2'] = $quoteBillingAddress->getStreetLine(2);
        $arr['BillingCity'] = $quoteBillingAddress->getCity();
        $arr['BillingPostCode'] = $quoteBillingAddress->getPostcode();
        $arr['BillingCountry'] = $quoteBillingAddress->getCountryId();
        $arr['BillingPhone'] = $quoteBillingAddress->getTelephone();
        if ($arr['BillingCountry'] == 'US') {
            $arr['BillingState'] = $quoteBillingAddress->getRegionCode();
        }
        $arr['DeliveryFirstnames'] = $quoteShippingAddress->getFirstname();
        $arr['DeliverySurname'] = $quoteShippingAddress->getLastname();
        $arr['DeliveryAddress1'] = $quoteShippingAddress->getStreetLine(1);
        $arr['DeliveryAddress2'] = $quoteShippingAddress->getStreetLine(2);
        $arr['DeliveryCity'] = $quoteShippingAddress->getCity();
        $arr['DeliveryPostCode'] = $quoteShippingAddress->getPostcode();
        $arr['DeliveryCountry'] = $quoteShippingAddress->getCountryId();
        $arr['DeliveryPhone'] = $quoteShippingAddress->getTelephone();
        if ($arr['DeliveryCountry'] == 'US') {
            $arr['DeliveryState'] = $quoteShippingAddress->getRegionCode();
        }

        return $arr;
    }

    /**
     * @param mixed $websiteId
     * @return array
     */
    public function getSageApiConfigArray($websiteId = null)
    {
        $env = $this->getApiEnv();
        $vendorName = $this->getConfigValue("vendor_name");
        $baseUrl = $this->_urlBuilder->getBaseUrl();
        $apply3Ds = $this->getIsApply3DSecure(false);
        $apply3Ds2 = $this->getIsApply3DSecureV2(); // apply 3D secure v2
        $browerJSEnable = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/browser_javascript_enabled',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        $applyCcv = $this->getIsApplyCvcCheck();
        $testDomain = $this->getConfigValue('test_domain');
        $liveDomain = $this->getConfigValue('live_domain');
        $testDomain = rtrim($testDomain, "/") . '/';
        $liveDomain = rtrim($liveDomain, "/") . '/';
        if (!$testDomain) {
            $testDomain = $baseUrl;
        }

        if (!$liveDomain) {
            $liveDomain = $baseUrl;
        }
        $collectRecipientDetails = boolval($this->getConfigValue('collect_recipient'));
        $sendEmail = $this->getConfigValue('send_email');
        $vendorEmail = $this->getConfigValue('vendor_email');
        if (!$vendorEmail) {
            $vendorEmail = "";
        }
        $language = $this->getConfigValue('payment_language');
        return [
            'env' => $env,
            'protocolVersion' => ($apply3Ds2) ? 4.00 : 3.00,
            'vendorName' => $vendorName,
            'siteFqdns' =>
                [
                    'live' => $liveDomain,
                    'test' => $testDomain,
                ],

            'partnerId' => '1BC70868-12A8-1383-A2FB-D7A0205DE97B',
            'vendorData' => '',
            'applyAvsCv2' => $this->convertApi3DsCcv($applyCcv),
            'apply3dSecure' => $this->convertApi3DsCcv($apply3Ds),
            'allowGiftAid' => $this->getConfigValue("gift_aid"),
            'surcharges' => $this->getSurchangeConfig(),
            'collectRecipientDetails' => $collectRecipientDetails,
            'formPassword' =>
                [
                    'test' => $this->getTestEncryptedPassword(),
                    'live' => $this->getLiveEncryptedPassword(),
                ],
            'formSuccessUrl' => 'sagepay/form/success',
            'formFailureUrl' => 'sagepay/form/failure',
            'directSuccessUrl' => 'sagepay/direct/success',
            'directFailureUrl' => 'sagepay/direct/failure',
            'accountType' => 'E',
            'serverNotificationUrl' => 'sagepay/server/notify',
            'paypalCallbackUrl' => 'sagepay/paypal/postBack',
            'sendEmail' => $sendEmail,
            'emailMessage' => '',
            'vendorEmail' => $vendorEmail,
// Optional parameter, this value will be used to set the BillingAgreement field in the registration POST
// A default is value of 0 is used if this parameter is not included in this properties file
            'customerPasswordSalt' => '',
            'basketAsXmlDisable' => false,
            'logError' => true,
            'language' => $language,
            'website' => $baseUrl,
            'requestTimeout' => 30,
            'caCertPath' => '',
            'BrowserJavascriptEnabled' => empty($browerJSEnable) ? 0 : 1,
            'ChallengeWindowSize' => '05',
            'BrowserAcceptHeader' => $this->request->getServer('HTTP_ACCEPT') ?? '',
            'BrowserLanguage' => explode(',', $this->request->getServer('HTTP_ACCEPT_LANGUAGE') ?? ',')[0],
            'BrowserUserAgent' => $this->request->getServer('HTTP_USER_AGENT') ?? '',
            'ClientIPAddress' => $this->request->getServer('SERVER_ADDR') ?? '',
            'TransType' => '01',
        ];
    }

    public function decryptResp($crypt)
    {
        $formPassword = $this->getEncryptedPassword();
        $decrypt = SagepayUtil::decryptAes($crypt, $formPassword);
        $decryptArr = SagepayUtil::queryStringToArray($decrypt);
        if (!$decrypt || empty($decryptArr)) {
            throw new SagepayApiException('Invalid crypt input');
        }

        return [
            'decrypt' => $decryptArr,
            'res' => [
                'status' => $decryptArr['Status'],
                'vpsTxId' => $decryptArr['VPSTxId'],
                'txAuthNo' => isset($decryptArr['TxAuthNo']) ? $decryptArr['TxAuthNo'] : '',
                'Surcharge' => isset($decryptArr['Surcharge']) ? $decryptArr['Surcharge'] : '',
                'BankAuthCode' => isset($decryptArr['BankAuthCode']) ? $decryptArr['BankAuthCode'] : '',
                'DeclineCode' => isset($decryptArr['DeclineCode']) ? $decryptArr['DeclineCode'] : '',
                'GiftAid' => isset($decryptArr['GiftAid']) && $decryptArr['GiftAid'] == 1,
                'avsCv2' => isset($decryptArr['AVSCV2']) ? $decryptArr['AVSCV2'] : '',
                'addressResult' => isset($decryptArr['AddressResult']) ? $decryptArr['AddressResult'] : '',
                'postCodeResult' => isset($decryptArr['PostCodeResult']) ? $decryptArr['PostCodeResult'] : '',
                'cv2Result' => isset($decryptArr['CV2Result']) ? $decryptArr['CV2Result'] : '',
                '3DSecureStatus' => isset($decryptArr['3DSecureStatus']) ? $decryptArr['3DSecureStatus'] : '',
                'CAVV' => isset($decryptArr['CAVV']) ? $decryptArr['CAVV'] : '',
                'cardType' => isset($decryptArr['CardType']) ? $decryptArr['CardType'] : '',
                'last4Digits' => isset($decryptArr['Last4Digits']) ? $decryptArr['Last4Digits'] : '',
                'expiryDate' => isset($decryptArr['ExpiryDate']) ? $decryptArr['ExpiryDate'] : '',
                'addressStatus' => isset($decryptArr['AddressStatus']) ? $decryptArr['AddressStatus'] : '',
                'payerStatus' => isset($decryptArr['PayerStatus']) ? $decryptArr['PayerStatus'] : ''
            ]
        ];
    }

    /**
     * @param $transactionId
     * @param mixed $order
     * @param mixed $websiteId
     * @return false|mixed|string[]
     */
    public function getTransactionDetail($transactionId, $order = null, $websiteId = null)
    {
        $mode = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/test',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        if ($mode == 1) {
            $url = 'https://test.sagepay.com/access/access.htm';
        } elseif ($mode == 0) {
            $url = 'https://live.sagepay.com/access/access.htm';
        }
        $vpstxid = $transactionId;
        $command = 'getTransactionDetail';
        $vendorName = $this->getConfigValue('vendor_name', false, $order);
        $userName = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/user',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        $password = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/password',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        $password = $this->_encryptor->decrypt($password);
        $signature = $this->getXmlSignature($command, $vpstxid, $vendorName, $userName, $password);
        $xml = '';
        $xml .= '<vspaccess>';
        $xml .= '<command>' . $command . '</command>';
        $xml .= '<vendor>' . $vendorName . '</vendor>';
        $xml .= '<user>' . $userName . '</user>';
        $xml .= '<vpstxid>' . $transactionId . '</vpstxid>';
        $xml .= '<signature>' . $signature . '</signature>';
        $xml .= '</vspaccess>';
        $response = $this->sendFormRequest($url, 'XML=' . $xml);
        return $response;
    }

    public function getXmlSignature($command, $vpstxid, $vendorName, $userName, $password)
    {
        $params = '<vpstxid>' . $vpstxid . '</vpstxid>';
        $xml = '<command>' . $command . '</command>';
        $xml .= '<vendor>' . $vendorName . '</vendor>';
        $xml .= '<user>' . $userName . '</user>';
        $xml .= $params;
        $xml .= '<password>' . $password . '</password>';
        return hash('md5', $xml);
    }

    public function arrayToQueryParams($postData)
    {
        $post_data_string = '';
        foreach ($postData as $_key => $_val) {
            $post_data_string .= $_key . '=' . urlencode(mb_convert_encoding($_val, 'ISO-8859-1', 'UTF-8')) . '&';
        }
        return $post_data_string;
    }

    public function refund($payment, $amount)
    {
        $order = $payment->getOrder();
        $mode = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/test',
            ScopeInterface::SCOPE_WEBSITE,
            $order->getStore()->getWebsiteId()
        );
        if ($mode == 1) { // test mode =  1
            $url = self::SAGEPAY_SHARED_REFUND_TRANSACTION_TEST;
        } elseif ($mode == 0) { //live mode = 0
            $url = self::SAGEPAY_SHARED_REFUND_TRANSACTION_LIVE;
        }
        $transaction = $this->getTransactionDetail(
            $payment->getAdditionalInformation('sagepay_transaction_id'),
            $order
        );
        $error = isset($transaction['error']) ? $transaction['error'] : '';
        $this->sageLogger->debug(var_export($transaction, true));
        if ($error) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($error)
            );
        }
        $payload['VPSProtocol'] = $this->getSageApiConfigArray()['protocolVersion'];
        $payload['TxType'] = SageHelper::SAGE_PAY_TYPE_REFUND;
        $payload['Vendor'] = $this->getSageApiConfigArray()['vendorName'];
        $payload['VendorTxCode'] = $this->generateVendorTxCode($order->getIncrementId(), "Refund", $order);
        $payload['Amount'] = number_format($amount, 2, '.', '');
        $payload['Currency'] = (string)$transaction['currency'];
        $payload['Description'] = 'Refund';
        $payload['RelatedVPSTxId'] = (string)$transaction['vpstxid'];
        $payload['RelatedVendorTxCode'] = (string)$transaction['vendortxcode'];
        $payload['RelatedSecurityKey'] = (string)$transaction['securitykey'];
        $payload['RelatedTxAuthNo'] = (string)$transaction['vpsauthcode'];

        $payload = $this->arrayToQueryParams($payload);
        $response = $this->sendFormRequest($url, $payload);
        $this->sageLogger->debug(var_export($response, true));
        $response = $this->parseResponseData($response);
        if (array_key_exists("Status", $response) && ($response['Status'] == 'OK')) {
            //refund success
            $transactionId = isset($response['VPSTxId']) ? $response['VPSTxId'] : "";
            $transactionId = str_replace(["{", "}"], "", $transactionId);
            $payment->setTransactionId($transactionId);
            $payment->setParentTransactionId($payment->getAdditionalInformation('transaction_id'));
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__("Refund Error"));
        }
        return $response;
    }

    /**
     * @param $transaction
     * @param $amount
     * @param mixed $websiteId
     * @return false|mixed|string[]
     */
    public function releaseDeferredTransaction($transaction, $amount, $websiteId = null)
    {
        $mode = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/test',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        if ($mode == 1) {
            $url = self::SAGEPAY_SHARED_RELEASE_TRANSACTION_TEST;
        } elseif ($mode == 0) {
            $url = self::SAGEPAY_SHARED_RELEASE_TRANSACTION_LIVE;
        }
        $payload['VPSProtocol'] = $this->getSageApiConfigArray()['protocolVersion'];
        $payload['TxType'] = self::SAGE_PAY_TYPE_RELEASE;
        $payload['Vendor'] = $this->getSageApiConfigArray()['vendorName'];
        $payload['VendorTxCode'] = (string)$transaction['vendortxcode'];
        $payload['ReleaseAmount'] = number_format($amount, 2, '.', '');
        $payload['VPSTxId'] = (string)$transaction['vpstxid'];
        $payload['SecurityKey'] = (string)$transaction['securitykey'];
        $payload['TxAuthNo'] = (string)$transaction['vpsauthcode'];

        $payload = $this->arrayToQueryParams($payload);
        $response = $this->sendFormRequest($url, $payload);
        return $response;
    }

    /**
     * @param $payment
     * @param mixed $websiteId
     * @return false|mixed|string[]
     */
    public function voidTransaction($payment, $websiteId = null)
    {
        $mode = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/test',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        if ($mode == 1) {
            $url = self::SAGEPAY_SHARED_VOID_TRANSACTION_TEST;
        } elseif ($mode == 0) {
            $url = self::SAGEPAY_SHARED_VOID_TRANSACTION_LIVE;
        }
        $transaction = $this->getTransactionDetail($payment->getAdditionalInformation('transaction_id'));
        $payload['VPSProtocol'] = $this->getSageApiConfigArray()['protocolVersion'];
        $payload['TxType'] = self::SAGE_PAY_TYPE_VOID;
        $payload['Vendor'] = $this->getSageApiConfigArray()['vendorName'];
        $payload['VendorTxCode'] = (string)$transaction['vendortxcode'];
        $payload['VPSTxId'] = (string)$transaction['vpstxid'];
        $payload['SecurityKey'] = (string)$transaction['securitykey'];
        $payload['TxAuthNo'] = (string)$transaction['vpsauthcode'];

        $response = $this->sendFormRequest($url, $payload);
        return $response;
    }

    public function parseResponseData($arr)
    {
        $dataReturn = [];
        foreach ($arr as $value) {
            $_value = explode("=", $value);
            if (isset($_value[0]) && isset($_value[1])) {
                $dataReturn[$_value[0]] = $_value[1];
            }
        }
        return $dataReturn;
    }

    /**
     * @param mixed $storeId
     * @return array
     */
    public function getSurchangeConfig($storeId = null)
    {
        $data = [];
        $surchangeConfig = $this->scopeConfig->getValue(
            'payment/magenest_sagepay/require/surcharge_config',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $values = $this->json->unserialize($surchangeConfig);
        $usedType = [];
        if ($values) {
            foreach ($values as $surchangeConfig) {
                $surchangeElement = $this->createSurchangeElement($surchangeConfig);
                if ($surchangeElement && !isset($usedType[$surchangeElement['paymentType']])) {
                    $data[] = $surchangeElement;
                    $usedType[$surchangeElement['paymentType']] = 1;
                }

            }
        }
        return $data;
    }

    protected function createSurchangeElement($surchangeConfig)
    {
        if ($surchangeConfig['payment_type'] && $surchangeConfig['surchange_type'] && $surchangeConfig['value']) {
            return [
                'paymentType' => $surchangeConfig['payment_type'],
                $surchangeConfig['surchange_type'] => $surchangeConfig['value']
            ];
        }
        return false;
    }

    /**
     * @param mixed $storeId
     * @return mixed
     */
    public function getPaymentProfileMode($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay_server/payment_profile',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param array $arr
     * @return string
     */
    public function parseErrorResponse($arr = [])
    {
        $arr = $this->json->unserialize($this->json->serialize($arr));
        $result = [];
        array_walk_recursive($arr, function ($v) use (&$result) {
            $result[] = $v;
        });
        return implode('. ', $result);
        //var_dump($result);
    }

    /**
     * @param mixed $storeId
     * @return mixed
     */
    public function getPaypalBillingAgreement($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'payment/magenest_sagepay_paypal/billing_agreement',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param array $data
     * @param \Magento\Quote\Model\Quote $quote
     * @param $type
     * @return array
     */
    public function getResponseData($data, $quote, $type)
    {
        if (is_array($data)) {
            $transactionId = isset($data['VPSTxId']) ? $data['VPSTxId'] : '';
            $transactionId = str_replace(["{", "}"], "", $transactionId);
            $arrData = [
                'transaction_id' => $transactionId,
                'vendor_tx_code' => isset($data['VendorTxCode']) ? $data['VendorTxCode'] : '',
                'transaction_type' => $type,
                'transaction_status' => $data['Status'] ?? '',
                'card_secure' => $data['3DSecureStatus'] ?? '',
                'status_detail' => $data['StatusDetail'] ?? '',
                'customer_id' => $quote->getCustomerId(),
                'customer_email' => $quote->getCustomerEmail() ?: $quote->getBillingAddress()->getEmail(),
                'quote_id' => $quote->getId(),
                'response_data' => json_encode($data)
            ];
            return $arrData;
        }
        return [];
    }

    public function getPiResponseData($response, $quote, $type)
    {
        $responseData = json_decode(json_encode($response), true);
        $threeD = isset($responseData['3DSecure']) ? $responseData['3DSecure'] : [];
        $dsecureStatus = isset($threeD['status']) ? $threeD['status'] : '';
        if (is_array($responseData)) {
            foreach ($responseData as $k => $v) {
                if (is_array($v)) {
                    unset($responseData[$k]);
                }
            }
        }
        $data = $responseData;
        if (is_array($data)) {
            $transactionId = isset($data['transactionId']) ? $data['transactionId'] : '';
            $arrData = [
                'transaction_id' => $transactionId,
                'transaction_type' => $type,
                'transaction_status' => $data['status'] ?? '',
                'card_secure' => $dsecureStatus,
                'status_detail' => $data['statusDetail'] ?? '',
                'customer_id' => $quote->getCustomerId(),
                'customer_email' => $quote->getCustomerEmail() ?: $quote->getBillingAddress()->getEmail(),
                'quote_id' => $quote->getId(),
                'response_data' => json_encode($data)
            ];
            return $arrData;
        }
        return [];
    }

    /**
     * Convert a object to a data object (used for repairing __PHP_Incomplete_Class objects)
     * @param array $d
     * @return array|mixed|object
     */
    public function arrayObjectToStdClass($d = [])
    {
        /**
         * If json_decode and json_encode exists as function, do it the simple way.
         * http://php.net/manual/en/function.json-encode.php
         */
        if (function_exists('json_decode') && function_exists('json_encode')) {
            return json_decode(json_encode($d), true);
        }
        $newArray = [];
        if (is_array($d) || is_object($d)) {
            foreach ($d as $itemKey => $itemValue) {
                if (is_array($itemValue)) {
                    $newArray[$itemKey] = (array)$this->arrayObjectToStdClass($itemValue);
                } elseif (is_object($itemValue)) {
                    $newArray[$itemKey] = (object)(array)$this->arrayObjectToStdClass($itemValue);
                } else {
                    $newArray[$itemKey] = $itemValue;
                }
            }
        }
        return $newArray;
    }
}
