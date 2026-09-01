<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

use Magenest\SagePay\Helper\SageHelper;
use Magento\Payment\Model\Method\AbstractMethod;

class AbstractSage extends AbstractMethod
{
    const CODE = 'magenest_sagepay_form';
    protected $_code = self::CODE;

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canCaptureOnce = true;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $sageLogger;
    protected $sageHelper;

    /**
     * SagePayForm constructor.
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param SageHelper $sageHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
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
        $this->sageHelper = $sageHelper;
        $this->sageLogger = $sageLogger;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return AbstractSage
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionId = $payment->getAdditionalInformation('sagepay_transaction_id');
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);

        $payment->setIsTransactionClosed(false);
        $payment->setShouldCloseParentTransaction(false);
        $payment->setAdditionalInformation("is_authorized", true);
        return parent::authorize($payment, $amount);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return SagePayForm
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->sageLogger->debug("Capture sage");
        $transactionId = $payment->getAdditionalInformation('sagepay_transaction_id');
        $isAuthorized = $payment->getAdditionalInformation("is_authorized");
        if ($isAuthorized) {
            $transaction = $this->sageHelper->getTransactionDetail($transactionId);
            $this->sageLogger->debug(var_export($transaction, true));
            $txStateId = isset($transaction['txstateid'])?$transaction['txstateid']:"";
            if ($txStateId) {
                if ($txStateId == SageHelper::DEFERRED_AWAITING_RELEASE) {
                    $response = $this->sageHelper->releaseDeferredTransaction($transaction, $amount);
                    $this->_debug($response);
                    $response = $this->sageHelper->parseResponseData($response);
                    if (isset($response['Status']) && ($response['Status']=='OK')) {
                        $payment->setIsTransactionClosed(true);
                        $payment->setShouldCloseParentTransaction(true);
                    } else {
                        throw new \Magento\Framework\Exception\LocalizedException(__("Capture exception"));
                    }
                }
            } else {
                $error = isset($transaction['error'])?$transaction['error']:"Payment exception";
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($error)
                );
            }
        }
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        return parent::capture($payment, $amount);
    }

    /**
     * @param array $debugData
     */
    protected function _debug($debugData)
    {
        $this->sageLogger->debug(var_export($debugData, true));
    }
}
