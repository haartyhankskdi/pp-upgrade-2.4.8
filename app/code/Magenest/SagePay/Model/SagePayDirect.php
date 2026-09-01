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
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magenest\SagepayLib\Classes\SagepaySettings;
use Magenest\SagepayLib\Classes\Constants;
use Magento\Sales\Api\OrderRepositoryInterface;

class SagePayDirect extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'magenest_sagepay_direct';
    const SAGE_PAY_TYPE_RELEASE = "Release";

    const RESPONSE_STATUS_NEED3D_SECURE = '3DAUTH';
    const STATUS_OK = 'OK';

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canCaptureOnce = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canOrder = false;
    protected $_canUseInternal = false;
    protected $formKey;
    protected $sageHelper;
    protected $checkoutSession;
    protected $_isInitializeNeeded = true;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $_serialize;

    /**
     * @var \Magenest\SagePay\Helper\Logger
     */
    protected $sageLogger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magenest\SagePay\Helper\Data
     */
    protected $dataHelper;

    /**
     * SagePayDirect constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param \Magenest\SagePay\Helper\Data $dataHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_serialize = $serialize;
        $this->formKey = $formKey;
        $this->checkoutSession = $checkoutSession;
        $this->sageHelper = $sageHelper;
        $this->sageLogger = $sageLogger;
        $this->_orderRepository = $orderRepository;
        $this->dataHelper = $dataHelper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function initialize($paymentAction, $stateObject)
    {
        $this->sageLogger->debug("Begin SagePay Direct");
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $checkMultipleShipping = $order->getQuote();
        $this->sageLogger->debug("orderId: " . $order->getIncrementId());
        $quote = $this->checkoutSession->getQuote();

        if (!$quote) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Invalid Quote")
            );
        }

        $response = $this->getApiResponse($order, $payment);
        if ($checkMultipleShipping && $this->checkMultiShipping($checkMultipleShipping) == true &&
            isset($response['ACSURL'])
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Payment error. Please turn off 3D Secure on SagePay")
            );
        }
        $this->_eventManager->dispatch(
            "magenest_sagepay_save_transaction",
            ['transaction_data' => $this->sageHelper->getResponseData($response, $order, "Direct")]
        );
        if ($paymentAction != PaymentAction::AUTHORIZE && $paymentAction != PaymentAction::AUTHORIZE_AND_CAPTURE) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Invaild payment action")
            );
        }

        if ($this->isPaymentSuccess($response)) {
            if ($paymentAction == PaymentAction::AUTHORIZE) {
                if ($this->isPaymentNeed3DSecure($response)) {
                    $this->save3DSecureInfo($response, $payment, true);
                } else {
                    $this->actionAndSaveNon3DSecureInfo($response, $payment, true);
                }
            } elseif ($paymentAction == PaymentAction::AUTHORIZE_AND_CAPTURE) {
                if ($this->isPaymentNeed3DSecure($response)) {
                    $this->save3DSecureInfo($response, $payment, false);
                } else {
                    $this->actionAndSaveNon3DSecureInfo($response, $payment, false);
                }
            }
            if (isset($response['MD'])) {
                $mdCode = $response['MD']; // 3D normal
            } elseif (isset($response['VPSTxId'])) {
                $mdCode = $response['VPSTxId']; // 3Dv2
            } else {
                $mdCode = '';
            }
            $order->setMdCode($mdCode);
            $this->_orderRepository->save($order);

            $this->updateStateObject($payment, $stateObject);
        } else {
            $statusDetail = isset($response['StatusDetail']) ? $response['StatusDetail'] : "Payment Error";
            throw new \Magento\Framework\Exception\LocalizedException(
                __($statusDetail)
            );
        }
        return parent::initialize($paymentAction, $stateObject);
    }

    public function updateStateObject($payment, $stateObject)
    {
        if ($payment->getOrder()) {
            $stateObject->setState($payment->getOrder()->getState());
            $stateObject->setStatus($payment->getOrder()->getStatus());
            $stateObject->setIsNotified(false);
        }
    }

    public function isPaymentNeed3DSecure($response)
    {
        return $response['Status'] == self::RESPONSE_STATUS_NEED3D_SECURE &&
            $response['3DSecureStatus'] == self::STATUS_OK;
    }

    public function checkMultiShipping(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        // not available if multiple shipping address
        return $quote->getIsMultiShipping();
    }

    public function isPaymentSuccess($response)
    {
        $status = isset($response['Status']) ? $response['Status'] : '';
        return $status == self::STATUS_OK || $this->isPaymentNeed3DSecure($response);
    }

    public function getApiResponse($quote, $payment)
    {
        $sageApi = $this->createSageApi($quote);
        $quoteDetails = $this->buildSageQuoteDetails($quote, $payment);
        $browserInfo = $this->getBrowserInfo($payment); // use in 3dv2
        $quoteDetails = array_merge($quoteDetails, $browserInfo);
        $api = $sageApi->buildApi($quote, $quoteDetails);
        if ($api) {
            $response = $api->createRequest();
            $this->sageHelper->debug('Direct Response');
            $this->sageHelper->debug($response);
            return array_merge($api->getData() ?? [], $response);
        }
        return false;
    }

    /**
     * @param $response
     * @param $payment InfoInterface
     * @param bool $isAuthorize
     */
    public function save3DSecureInfo($response, $payment, $isAuthorize = false)
    {
        $payment->setAdditionalInformation('3d_secure_response', json_encode($response));
        $md = isset($response['MD']) ? $response['MD'] : '';
        $creq = isset($response['CReq']) ? $response['CReq'] : '';
        $payment->setTransactionId($md);
        $payment->setCreq($creq);
        if ($isAuthorize) {
            $payment->setAdditionalInformation('is_authorize', true);
        }
    }

    public function actionAndSaveNon3DSecureInfo($response, $payment, $isAuthorize = false)
    {
        if ($isAuthorize) {
            $this->authorizeAndSaveInfo($response, $payment);
        } else {
            $this->captureAndSaveInfo($response, $payment);
        }
    }

    public function authorizeAndSaveInfo($response, $payment)
    {
        $status = isset($response['Status']) ? $response['Status'] : '';
        if ($status == self::STATUS_OK) {
            $this->saveTransactionInfo($response, $payment);
            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);
            $payment->setAdditionalInformation("is_authorized", true);
        }
        /** @var  $order Order */
        $order = $payment->getOrder();
        $amount = $this->dataHelper->getPayAmount($order);
        if ($order && $amount) {
            $payment->authorize(true, $amount);
        }
    }

    /**
     * @param $response
     * @param $payment
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function captureAndSaveInfo($response, $payment)
    {
        $status = isset($response['Status']) ? $response['Status'] : '';

        if ($status == self::STATUS_OK) {
            $this->saveTransactionInfo($response, $payment);
        }
        /** @var  $order Order */
        $order = $payment->getOrder();
        $amount = $this->dataHelper->getPayAmount($order);
        if ($order && $amount && $this->canCapture()) {
            $payment->capture();
        }
    }

    /**
     * @param $response
     * @param $payment
     */
    public function saveTransactionInfo($response, $payment)
    {
        $status = isset($response['Status']) ? $response['Status'] : '';
        if ($status == self::STATUS_OK) {
            $transactionId = isset($response['VPSTxId']) ? $response['VPSTxId'] : '';
            $transactionDetail = $this->sageHelper->getTransactionDetail($transactionId);
            $response['transactionType'] = "Direct";
            $response['status'] = $status;
            $response['statusDetail'] = isset($transactionDetail['status']) ? $transactionDetail['status'] : '';
            $response['3DSecure'] = [
                'status' => isset($response['3DSecureStatus']) ? $response['3DSecureStatus'] : ''
            ];
            $transactionId = str_replace(["{", "}"], "", $transactionId);
            $payment->setTransactionId($transactionId);
            $payment->setLastTransId($transactionId);
            $payment->setAdditionalInformation('sagepay_transaction_id', $transactionId);
            $payment->setAdditionalInformation('sagepay_response', $this->_serialize->serialize($response));
        }
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function captureExistedTransaction($payment)
    {
        $order = $payment->getOrder();
        $amount = $this->dataHelper->getPayAmount($order);
        if (!$order || !$amount) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Payment Error")
            );
        }
        $transaction = $this->sageHelper->getTransactionDetail(
            $payment->getAdditionalInformation('sagepay_transaction_id')
        );
        $response = $this->sageHelper->releaseDeferredTransaction($transaction, $amount);
        $status = isset($response[1]) ? $response[1] : '';
        if ($status == "Status=OK") {
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            $payment->setAdditionalInformation("is_authorized", false);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException('Capture Error');
        }
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $additionalData = $data->getData('additional_data');
        parent::assignData($data);
        if (isset($additionalData['isSageDirect'])) {
            $quote = $this->checkoutSession->getQuote();
            $payment = $quote->getPayment();
            $payment->setAdditionalInformation('customerBrowserInfo', $additionalData['browserInfo'] ?? '');
            $payment->setAdditionalInformation('billingAddress', $additionalData['billing_address'] ?? '');
            $payment->setAdditionalInformation('shippingAddress', $additionalData['shipping_address'] ?? '');
            $payment->setAdditionalInformation('cc_number', $additionalData['cc_number'] ?? '');
            $payment->setAdditionalInformation('cc_cid', $additionalData['cc_cid'] ?? '');
        }
        return $this;
    }

    public function release($payment)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $payload['VPSProtocol'] = $this->sageHelper->getSageApiConfigArray()['protocolVersion'];
        $payload['TxType'] = self::SAGE_PAY_TYPE_RELEASE;
        $payload['Vendor'] = $this->sageHelper->getSageApiConfigArray()['vendorName'];
        $payload['VendorTxCode'] = $this->generateVendorTxCode($order->getIncrementId());
        $payload['Amount'] = 1;
        $payload['Currency'] = $this->dataHelper->getCurrency($order);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return SagePayDirect
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($payment->getAdditionalInformation('is_authorized')) {
            $this->captureExistedTransaction($payment);
        }
        return parent::capture($payment, $amount);
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return SagePayDirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->sageHelper->refund($payment, $amount);
        return parent::refund($payment, $amount);
    }

    /**
     * @return bool
     */
    public function hasVerification()
    {
        return true;
    }

    /**
     * @return $this|SagePayDirect
     */
    public function validate()
    {
        return $this;
    }

    /**
     * @return SagepaySettings|null
     */
    public function getSagePayConfig()
    {
        $config = [
            'txType' => $this->sageHelper->getSageDirectAction()
        ];
        $apiConfig = array_merge_recursive($this->sageHelper->getSageApiConfigArray(), $config);
        $sageConfig = SagepaySettings::getInstance($apiConfig, false);
        return $sageConfig;
    }

    /**
     * @param $quote
     * @return SagepayAPI
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createSageApi($quote)
    {
        $config = [
            'currency' => $this->dataHelper->getCurrency($quote),
            'txType' => $this->sageHelper->getSageDirectAction(),
            'ThreeDSNotificationURL' => 'sagepay/direct/postBack?form_key=' . $this->formKey->getFormKey()
        ];
        $apiConfig = array_merge_recursive($this->sageHelper->getSageApiConfigArray(), $config);
        $sageConfig = SagepaySettings::getInstance($apiConfig, false);
        $sageApi = new SagepayAPI($this->dataHelper, $sageConfig, Constants::SAGEPAY_DIRECT);
        return $sageApi;
    }

    /**
     * @param $quote
     * @param InfoInterface $payment
     * @return array
     */
    public function buildSageQuoteDetails($quote, \Magento\Payment\Model\InfoInterface $payment)
    {
        $billingAddress = $quote->getBillingAddress();
        $guessEmail = $billingAddress->getEmail();
        $quoteDetails = $this->sageHelper->getPaymentDetail($quote, $guessEmail);

        $expireMonth = $payment->getData('cc_exp_month');
        if ($payment->getData('cc_exp_month') < 10) {
            $expireMonth = '0' . $payment->getData('cc_exp_month');
        }
        $expireYear = $payment->getData('cc_exp_year')[2] . $payment->getData('cc_exp_year')[3];
        $expireDate = $expireMonth . $expireYear;
        $cardType = $payment->getData('cc_type');
        $card = [
            'cardType' => $cardType,
            'cardNumber' => $payment->getData('cc_number'),
            'cardHolder' => $payment->getData('cc_owner'),
            'startDate' => ' ',
            'expiryDate' => $expireDate,
            'cv2' => $payment->getData('cc_cid'),
        ];
        $quoteDetails['CardType'] = $card['cardType'];
        $quoteDetails['CardNumber'] = $card['cardNumber'];
        $quoteDetails['CardHolder'] = $card['cardHolder'];
        $quoteDetails['ExpiryDate'] = $card['expiryDate'];
        $quoteDetails['CV2'] = $card['cv2'];
        return $quoteDetails;
    }

    /**
     * @param InfoInterface $payment
     * @return array
     */
    public function getBrowserInfo(\Magento\Payment\Model\InfoInterface $payment)
    {
        $customerBrowserInfo = json_decode($payment->getAdditionalInformation()['customerBrowserInfo'], true);
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
