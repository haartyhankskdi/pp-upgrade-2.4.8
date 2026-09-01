<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Server;

use Magenest\SagepayLib\Classes\Constants;
use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\QuoteValidator;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magenest\SagepayLib\Classes\SagepayUtil;

class Notify extends Action
{
    protected $coreRegistry;

    protected $_formKeyValidator;

    protected $checkoutSession;

    protected $sageHelper;

    protected $sageLogger;

    protected $quoteValidator;

    protected $customerManagement;

    protected $dataHelper;

    protected $cache;

    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        CustomerManagement $customerManagement,
        QuoteValidator $quoteValidator,
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magento\Framework\App\CacheInterface $cache
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
        $this->cache = $cache;
    }

    public function execute()
    {
        $this->sageLogger->debug("Begin SagePay Server notify");
        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $siteFqdn = $this->sageHelper->getSageApiConfigArray()['website'];
        $vtxData = filter_input_array(INPUT_POST);
        $transactionId = isset($vtxData['VPSTxId']) ? $vtxData['VPSTxId'] : '';
        $transactionId = str_replace(["{", "}"], "", $transactionId);

        $this->_eventManager->dispatch(
            "magenest_sagepay_save_transaction",
            ['transaction_data' => $this->sageHelper->getResponseData($vtxData, $quote, "Server")]
        );

        $this->sageLogger->debug(var_export($vtxData, true));
        if (in_array(
            filter_input(INPUT_POST, 'Status'),
            [
                Constants::SAGEPAY_REMOTE_STATUS_OK,
                Constants::SAGEPAY_REMOTE_STATUS_AUTHENTICATED,
                Constants::SAGEPAY_REMOTE_STATUS_REGISTERED
            ]
        )) {
            $surcharge = floatval(filter_input(INPUT_POST, 'Surcharge', FILTER_VALIDATE_FLOAT));
            $vtxData['Amount'] = $payment->getAmount() + $surcharge;
            if (filter_input(INPUT_POST, 'TxType') == Constants::SAGEPAY_REMOTE_STATUS_PAYMENT) {
                $vtxData['CapturedAmount'] = $vtxData['Amount'];
            }
            $data = [
                "Status" => Constants::SAGEPAY_REMOTE_STATUS_OK,
                "RedirectURL" => $siteFqdn . 'sagepay/server/success?vtx=' .
                    filter_input(INPUT_POST, 'VendorTxCode'),
                "StatusDetail" => __('The transaction was successfully processed.')
            ];
        } else {
            $data = [
                "Status" => Constants::SAGEPAY_REMOTE_STATUS_OK,
                "RedirectURL" => $siteFqdn . 'sagepay/server/failure?vtx=' .
                    filter_input(INPUT_POST, 'VendorTxCode'),
                "StatusDetail" => filter_input(INPUT_POST, 'StatusDetail')
            ];
        }
        $vtxData['AllowGiftAid'] = filter_input(INPUT_POST, 'GiftAid');
        $payment->addData($vtxData);
        $vendorTxCode = isset($vtxData['VendorTxCode']) ? $vtxData['VendorTxCode'] : '';
        if ($vendorTxCode) {
            $this->cache->save(json_encode($data), $vendorTxCode);
        }
        return $this->resultFactory->create('raw')->setContents(SagepayUtil::arrayToQueryString($data, "\n"));
    }
}
