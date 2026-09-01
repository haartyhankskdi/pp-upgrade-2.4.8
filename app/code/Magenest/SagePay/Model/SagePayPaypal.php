<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

use Magenest\SagePay\Helper\SagepayAPI;
use Magenest\SagePay\Model\Source\PaymentAction;
use Magenest\SagepayLib\Classes\SagepayUtil;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\InfoInterface;
use Magenest\SagepayLib\Classes\SagepaySettings;
use Magenest\SagepayLib\Classes\Constants;

class SagePayPaypal extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * payment code
     */
    const CODE = 'magenest_sagepay_paypal';

    /**
     * @var string
     */
    protected $_code = self::CODE;
    /**
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * @var bool
     */
    protected $_canCapture = true;
    /**
     * @var bool
     */
    protected $_canCapturePartial = true;
    /**
     * @var bool
     */
    protected $_canCaptureOnce = true;
    /**
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * @var bool
     */
    protected $_canVoid = true;
    /**
     * @var bool
     */
    protected $_canOrder = false;
    /**
     * @var bool
     */
    protected $_canUseInternal = false;
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;
    /**
     * @var \Magenest\SagePay\Helper\SageHelper
     */
    protected $sageHelper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var
     */
    protected $sageDirectModel;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;
    /**
     * @var \Magenest\SagePay\Helper\Logger
     */
    protected $sageLogger;
    /**
     * @var \Magenest\SagePay\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var Json
     */
    private $json;

    /**
     * SagePayPaypal constructor.
     * @param \Magenest\SagePay\Helper\Data $dataHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magento\Framework\UrlInterface $url
     * @param Json $json
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magento\Framework\UrlInterface $url,
        Json $json,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->url = $url;
        $this->json = $json;
        $this->checkoutSession = $session;
        $this->sageHelper = $sageHelper;
        $this->formKey = $formKey;
        $this->sageLogger = $sageLogger;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return \Magenest\SagePay\Model\SagePayPaypal
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->sageLogger->debug("Begin SagePay Paypal");
        $payment = $this->getInfoInstance();
        $this->sageLogger->debug("orderId: " . $payment->getOrder()->getIncrementId());
        $quote = $this->checkoutSession->getQuote();

        if (!$quote) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Invalid Quote")
            );
        }

        if ($paymentAction != PaymentAction::AUTHORIZE && $paymentAction != PaymentAction::AUTHORIZE_AND_CAPTURE) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Invaild payment action")
            );
        }

        $response = $this->getApiResponse($quote, $payment);
        if ($this->isPaymentSuccess($response)) {
            $this->savePaymentInformation($payment, $response, $paymentAction);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Payment Error: " . $response['StatusDetail'])
            );
        }
        return parent::initialize($paymentAction, $stateObject);
    }

    /**
     * @param $response
     * @return bool
     */
    public function isPaymentSuccess($response)
    {
        $status = isset($response['Status']) ? $response['Status'] : '';
        return $status == Constants::SAGEPAY_REMOTE_STATUS_PAYPAL_REDIRECT;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $response
     * @param $paymentAction
     */
    public function savePaymentInformation(InfoInterface $payment, $response, $paymentAction)
    {
        if ($paymentAction == PaymentAction::AUTHORIZE) {
            $payment->setAdditionalInformation('is_authorize', true);
        }
        $transactionId = isset($response['VPSTxId']) ? $response['VPSTxId'] : '';
        $transactionId = str_replace(["{", "}"], "", $transactionId);
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        $payment->setAdditionalInformation('transaction_id', $transactionId);
        $payment->setAdditionalInformation('paypal_redirect_url', $response['PayPalRedirectURL'] ?? '');
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return $this|\Magenest\SagePay\Model\SagePayPaypal
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $additionalData = $data->getData('additional_data');
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $payment->setAdditionalInformation('customerBrowserInfo', $additionalData['browserInfo'] ?? '');
        $payment->setAdditionalInformation('billingAddress', $additionalData['billing_address'] ?? '');
        $payment->setAdditionalInformation('shippingAddress', $additionalData['shipping_address'] ?? '');
        return $this;
    }

    /**
     * @param $quote
     * @param $payment
     * @return false|string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getApiResponse($quote, $payment)
    {
        $sageApi = $this->createSageApi($quote);
        $quoteDetails = $this->buildSageQuoteDetails($quote, $payment);
        $browserInfo = $this->getBrowserInfo($payment); // use in 3dv2
        $quoteDetails = array_merge($quoteDetails, $browserInfo);
        $api = $sageApi->buildApi($quote, $quoteDetails);
        $api->setIntegrationMethod(Constants::SAGEPAY_PAYPAL);
        if ($api) {
            $response = $api->createRequest();
            $requestForLog = SagepayUtil::arrayToQueryStringRemovingSensitiveData(
                $api->getData(),
                array_keys($api->getData())
            );
            $this->sageHelper->debug("Paypal Request");
            $this->sageLogger->debug($requestForLog);
            $this->sageHelper->debug("Paypal Response");
            $this->sageHelper->debug($response);
            return $response;
        }
        return false;
    }

    /**
     * @param $quote
     * @param $payment
     * @return array
     */
    public function buildSageQuoteDetails($quote, $payment)
    {
        $billingAddress = $quote->getBillingAddress();
        $guessEmail = $billingAddress->getEmail();
        $quoteDetails = $this->sageHelper->getPaymentDetail($quote, $guessEmail);
        $quoteDetails['CardType'] = 'PAYPAL';
        return $quoteDetails;
    }

    /**
     * @param $quote
     * @return \Magenest\SagePay\Helper\SagepayAPI
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createSageApi($quote)
    {
        $config = [
            'currency' => strtoupper($quote->getBaseCurrencyCode()),
            'txType' => $this->sageHelper->getSageDirectPaypalAction(),
            'billingAgreement' => (int)$this->sageHelper->getPaypalBillingAgreement(),
            'ThreeDSNotificationURL' => 'sagepay/paypal/postBack?form_key=' . $this->formKey->getFormKey()
        ];
        $apiConfig = array_merge_recursive($this->sageHelper->getSageApiConfigArray(), $config);
        $sageConfig = SagepaySettings::getInstance($apiConfig, false);
        $sageApi = new SagepayAPI($this->dataHelper, $sageConfig, 'direct');
        return $sageApi;
    }

    /**
     * @param InfoInterface $payment
     * @return array
     */
    public function getBrowserInfo(\Magento\Payment\Model\InfoInterface $payment)
    {
        $customerBrowserInfo = $this->json->unserialize(
            $payment->getAdditionalInformation('customerBrowserInfo')
        );
        $colorDepth = $customerBrowserInfo['BrowserColorDepth'];
        $browserColorDepthArrayAllow = [48,32,24,16,15,8,4,1];
        if (!in_array($colorDepth, $browserColorDepthArrayAllow)) {
            foreach ($browserColorDepthArrayAllow as $item) {
                if ($colorDepth > $item) {
                    $colorDepth = $item;
                    break;
                }
            }
        }
        return [
            'BrowserJavaEnabled' => $customerBrowserInfo['BrowserJavaEnabled'] ? 1 : 0,
            'BrowserColorDepth' => $colorDepth,
            'BrowserScreenHeight' => $customerBrowserInfo['BrowserScreenHeight'],
            'BrowserScreenWidth' => $customerBrowserInfo['BrowserScreenWidth'],
            'BrowserTZ' => $customerBrowserInfo['BrowserTZ']
        ];
    }
}
