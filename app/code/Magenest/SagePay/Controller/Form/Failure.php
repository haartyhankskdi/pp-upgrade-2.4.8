<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Form;

use Magenest\SagepayLib\Classes\SagepayApiException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Failure extends Action
{
    protected $checkoutSession;

    protected $sageHelper;

    protected $cartRepository;

    protected $quoteFactory;

    protected $_transactionCollectionFactory;

    /**
     * Failure constructor.
     * @param Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magenest\SagePay\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magenest\SagePay\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->sageHelper = $sageHelper;
        $this->quoteFactory = $quoteFactory;
        $this->cartRepository = $cartRepository;
        $this->_transactionCollectionFactory = $transactionCollectionFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $this->checkoutSession->setLoadInactive(true);
            $crypt = $this->getRequest()->getParam('crypt');
            $response = $this->sageHelper->decryptResp($crypt);

            $decryptResponse = $response['decrypt'] ?? [];
            $vendorTxCode = $decryptResponse['VendorTxCode'] ?? '';
            $status = $decryptResponse['Status'] ?? __("Error");
            $statusDetail = $decryptResponse['StatusDetail'] ?? __("Payment error");

            $transaction = $this->_transactionCollectionFactory->create()
                ->addFieldToFilter('vendor_tx_code', $vendorTxCode)->getLastItem();
            $quote = $this->quoteFactory->create()->load($transaction->getQuoteId());
            $quote->setIsActive(true);
            $this->cartRepository->save($quote);
            $this->_eventManager->dispatch(
                "magenest_sagepay_save_transaction",
                ['transaction_data' => $this->sageHelper->getResponseData($decryptResponse, $quote, "Form")]
            );
            $this->messageManager->addErrorMessage($status." - ".$statusDetail);
            return $this->_redirect("checkout/cart");
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("Payment exception"));
            return $this->_redirect("checkout/cart");
        }
    }
}
