<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Server;

use Magenest\SagePay\Helper\SageHelper;
use Magenest\SagePay\Helper\SagepayAPI;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\QuoteValidator;
use Magenest\SagepayLib\Classes\SagepayApiException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magenest\SagepayLib\Classes\SagepaySettings;
use Magenest\SagepayLib\Classes\Constants;

class Build extends Action
{
    protected $coreRegistry;

    protected $_formKeyValidator;

    protected $checkoutSession;

    protected $sageHelper;

    protected $sageLogger;

    protected $quoteValidator;

    protected $customerManagement;

    protected $dataHelper;

    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        CustomerManagement $customerManagement,
        QuoteValidator $quoteValidator,
        \Magenest\SagePay\Helper\Data $dataHelper
    ) {
        $this->coreRegistry = $registry;
        $this->_formKeyValidator = $formKeyValidator;
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->sageHelper = $sageHelper;
        $this->sageLogger = $sageLogger;
        $this->quoteValidator = $quoteValidator;
        $this->customerManagement = $customerManagement;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        try {
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            if (!$this->_formKeyValidator->validate($this->getRequest())) {
                throw new SagepayApiException(__("Invalid Form Key"));
            }
            if ($this->getRequest()->isAjax()) {
                $quote = $this->checkoutSession->getQuote();
                $this->quoteValidator->validateBeforeSubmit($quote);
                if (!$quote->getCustomerIsGuest()) {
                    if ($quote->getCustomerId()) {
                        $this->customerManagement->validateAddresses($quote);
                    }
                }
                $guestEmail = $this->getRequest()->getParam('guest_email');

                $quoteDetails = $this->sageHelper->getPaymentDetail($quote, $guestEmail);
                $paymentProfile = $this->sageHelper->getPaymentProfileMode();
                $config = [
                    'currency' => $this->dataHelper->getCurrency($quote),
                    'txType' => $this->sageHelper->getSageServerPaymentAction()
                ];
                $apiConfig = array_merge_recursive($this->sageHelper->getSageApiConfigArray(), $config);
                $sageConfig = SagepaySettings::getInstance($apiConfig, false);
                $sageConfig->setSiteFqdn($apiConfig['website']);
                if ($paymentProfile == Constants::SAGEPAY_SERVER_PROFILE_LOW) {
                    $sageConfig->setServerProfile(Constants::SAGEPAY_SERVER_PROFILE_LOW);
                } elseif ($paymentProfile == Constants::SAGEPAY_SERVER_PROFILE_NORMAL) {
                    $sageConfig->setServerProfile(Constants::SAGEPAY_SERVER_PROFILE_NORMAL);
                } else {
                    $result->setData([
                        'error' => true,
                        'message' => __("Payment error")
                    ]);
                    return $result;
                }
                $sageApi = new SagepayAPI($this->dataHelper, $sageConfig, Constants::SAGEPAY_SERVER);
                $api = $sageApi->buildApi($quote, $quoteDetails);
                $api->setVpsServerUrl($sageConfig->getPurchaseUrl(Constants::SAGEPAY_SERVER, $sageConfig->getEnv()));
                if ($api) {
                    $request = $api->createRequest();
                    if ($request['Status'] != Constants::SAGEPAY_REMOTE_STATUS_OK) {
                        $result->setData([
                            'success' => false,
                            'error' => true,
                            'message' => $request['StatusDetail']
                        ]);
                        return $result;
                    }
                    $vendorTxCode = $api->getData()['VendorTxCode'];
                    $quote->setPaymentMethod('magenest_sagepay_server');
                    $quote->getPayment()->setAdditionalInformation("vendor_tx_code", $vendorTxCode);
                    $quote->getPayment()->importData(['method' => 'magenest_sagepay_server']);
                    $data = array_merge($api->getData(), $request);
                    $this->checkoutSession->setData('magenest_sagepay_server', $data);
                    $quote->getPayment()->addData($data);
                    $quote->save();
                    $this->sageLogger->debug("Begin SagePay Server");
                    $this->sageLogger->debug(var_export($api->getData(), true));
                    $result->setData([
                        'success' => true,
                        'request' => $request,
                        'purchaseUrl' => $sageConfig->getPurchaseUrl(Constants::SAGEPAY_SERVER, $sageConfig->getEnv()),
                        'profile' => $paymentProfile,
                        'nextUrl' => $request['NextURL']
                    ]);
                } else {
                    $result->setData([
                        'error' => true,
                        'message' => __("Payment Request Error")
                    ]);
                }
            } else {
                $result->setData([
                    'error' => true,
                    'message' => __("Invalid request")
                ]);
            }
        } catch (SagepayApiException $e) {
            $this->dataHelper->debugException($e);
            $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        } catch (\Magento\Framework\Validator\Exception $e) {
            $this->dataHelper->debugException($e);
            $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        } catch (LocalizedException $e) {
            $this->dataHelper->debugException($e);
            $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->dataHelper->debugException($e);
            $result->setData([
                'error' => true,
                'message' => __("Payment error")
            ]);
        } finally {
            return $result;
        }
    }
}
