<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Direct;

use Magenest\SagePay\Helper\SagepayAPI;
use Magenest\SagepayLib\Classes\Constants;
use Magenest\SagepayLib\Classes\SagepayApiException;
use Magenest\SagepayLib\Classes\SagepaySettings;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Build extends Action
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magenest\SagePay\Helper\SageHelper
     */
    protected $sageHelper;

    /**
     * @var \Magenest\SagePay\Helper\Logger
     */
    protected $sageLogger;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManager;

    /**
     * @var \Magenest\SagePay\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    private $decoder;

    /**
     * Build constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magenest\SagePay\Helper\Data $dataHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Url\DecoderInterface $decoder
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Url\DecoderInterface $decoder
    ) {
        $this->quoteManager = $quoteManagement;
        $this->coreRegistry = $registry;
        $this->_formKeyValidator = $formKeyValidator;
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->sageHelper = $sageHelper;
        $this->sageLogger = $sageLogger;
        $this->dataHelper = $dataHelper;
        $this->escaper = $escaper;
        $this->decoder = $decoder;
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        try {
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            if (!$this->_formKeyValidator->validate($this->getRequest())) {
                $result->setData([
                    'error' => true,
                    'message' => __('Invalid Form Key')
                ]);
                return $result;
            }
            if ($this->getRequest()->isAjax()) {
                $cardType = $this->getRequest()->getParam('cc_type');
                $card = $this->getRequest()->getParam('card');
                $guestEmail = $this->getRequest()->getParam('guest_email');
                $quote = $this->checkoutSession->getQuote();
                $quoteDetails = $this->sageHelper->getPaymentDetail($quote, $guestEmail);
                $config = [
                    'currency' => $this->dataHelper->getCurrency($quote),
                    'txType' => $this->sageHelper->getSageFormPaymentAction()
                ];
                $apiConfig = array_merge_recursive($this->sageHelper->getSageApiConfigArray(), $config);
                $sageConfig = SagepaySettings::getInstance($apiConfig, false);
                $sageApi = new SagepayAPI($this->dataHelper, $sageConfig, 'direct');
                $quoteDetails['CardType'] = $cardType;
                $quoteDetails['cardType'] = $card['cardType'];
                $quoteDetails['cardNumber'] = $card['cardNumber'];
                $quoteDetails['cardHolder'] = $card['cardHolder'];
                $quoteDetails['expiryDate'] = $card['expiryDate'];
                $quoteDetails['cv2'] = $card['cv2'];
                $api = $sageApi->buildApi($quote, $quoteDetails);
                if ($api) {
                    $request = $api->createRequest();
                    $vendorTxCode = $api->getData()['VendorTxCode'];
                    $quote->setPaymentMethod('magenest_sagepay_direct');
                    $quote->getPayment()->setAdditionalInformation("vendor_tx_code", $vendorTxCode);
                    if ($request['Status'] == "3DAUTH") {
                        $quote->getPayment()->setAdditionalInformation("sage_3ds_active", "true");
                        $quote->getPayment()->setAdditionalInformation("sage_3ds_url", $request['ACSURL']);
                        $quote->getPayment()->setAdditionalInformation("sage_3ds_pareq", $request['PAReq']);
                        $quote->getPayment()->setAdditionalInformation("sage_trans_id_secure", $request['MD']);
                    } elseif ($request['Status'] !="3DAUTH") {
                        $quote->getPayment()->setAdditionalInformation("sage_3ds_active", "false");

                    }
                    $quote->save();
                    $queryString = $this->escaper->escapeHtml(
                        $this->decoder->decode(utf8_encode($api->getQueryData()))
                    );
                    $result->setData([
                        'success' => true,
                        'request' => $request,
                        'purchaseUrl' => $sageConfig->getPurchaseUrl(
                            Constants::SAGEPAY_DIRECT,
                            $sageConfig->getEnv()
                        ),
                        'string' => $queryString
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
        } catch (\Exception $e) {
            $result->setData([
                'error' => true,
                'message' => __("Payment error")
            ]);
        } finally {
            return $result;
        }
    }
}
