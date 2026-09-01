<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Server;

use Magenest\SagepayLib\Classes\SagepayApiException;
use Magento\Framework\App\Action\Context;

class Success extends \Magenest\SagePay\Controller\Success
{

    /**
     * @var \Magenest\SagePay\Model\TransactionFactory
     */
    private $transaction;

    /**
     * Success constructor.
     * @param Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param \Magenest\SagePay\Helper\Data $dataHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magenest\SagePay\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory
     * @param \Magenest\SagePay\Model\TransactionFactory $transaction
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magenest\SagePay\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory,
        \Magenest\SagePay\Model\TransactionFactory $transaction
    ) {
        $this->transaction = $transaction;
        parent::__construct(
            $context,
            $checkoutSession,
            $sageHelper,
            $customerSession,
            $orderFactory,
            $quoteFactory,
            $quoteManagement,
            $sageLogger,
            $orderSender,
            $serialize,
            $dataHelper,
            $cartRepository,
            $transactionCollectionFactory
        );
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->_checkoutSession->getData('magenest_sagepay_server');
        if (!$data) {
            return $this->_redirect("*/*/redirectsuccess");
        }
        try {
            $status = isset($data['Status']) ? $data['Status'] : "Complete";
            $statusDetail = isset($data['StatusDetail']) ? $data['StatusDetail'] : __("Payment Complete");
            $vendorTxCode = isset($data['VendorTxCode']) ? $data['VendorTxCode'] : "";
            $cardSecure = isset($data['3DSecureStatus']) ? $data['3DSecureStatus'] : "";
            $transactionId = isset($data['VPSTxId']) ? $data['VPSTxId'] : "";
            $transactionId = str_replace(["{", "}"], "", $transactionId);
            $transactionModel = $this->transaction->create()->load($transactionId, "transaction_id");
            $quote = $this->_checkoutSession->getQuote();
            if ($transactionModel->getId()) {
                $transactionModel->setData("quote_id", $quote->getId());
                $transactionModel->save();
            }
            $quote = $this->_checkoutSession->getQuote();
            $quote->getPayment()->setAdditionalInformation("sagepay_transaction_id", $transactionId);
            if (!$this->customerSession->isLoggedIn()) {
                $quote->setCheckoutMethod(\Magento\Quote\Model\QuoteManagement::METHOD_GUEST);
            }
            $quote->save();
            $payment = $quote->getPayment();
            if (($status == "OK") && ($vendorTxCode == $payment->getAdditionalInformation('vendor_tx_code'))) {
                //create order
                $quote->getPayment()->setMethod('magenest_sagepay_server');

                $sagedata = [
                    'transactionType' => 'Server',
                    'status' => $status,
                    'card_secure' => $cardSecure,
                    'vendorTxCode' => $vendorTxCode,
                    'statusDetail' => $statusDetail
                ];
                $payment->setAdditionalInformation("sagepay_response", $this->_serialize->serialize($sagedata));
                $quote->save();

                $orderId = $this->quoteManagement->placeOrder($quote->getId(), $payment);
                $order = $this->orderFactory->create()->load($orderId);
                $payment = $order->getPayment();
                $order->addStatusHistoryComment($statusDetail);
                $payment->setIsTransactionClosed(0);
                $payment->setShouldCloseParentTransaction(0);
                $payment->setAdditionalInformation('vendorTxCode', $vendorTxCode);
                $payment->setAdditionalInformation('profile', $data['Profile']);
                $payment->setAdditionalInformation('securityKey', $data['SecurityKey']);
                $payment->setAdditionalInformation('referrerId', $data['ReferrerID']);
                $order->save();
                $items = $order->getAllItems();
                $payment->setAdditionalInformation("sagepay_response", $this->_serialize->serialize($data));
                $increment_id = $order->getRealOrderId();
                $this->messageManager->addSuccessMessage("Your order (ID: $increment_id) was successful!");
                $this->messageManager->addSuccessMessage($status . " - " . $statusDetail);
                $this->_checkoutSession->unsData('magenest_sagepay_server');
                return $this->_redirect("checkout/onepage/success");
            } else {
                $e = new SagepayApiException($status." - ".$statusDetail);
                $this->dataHelper->debugException($e);
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->sageLogger->critical($e->getMessage());
                return $this->_redirect("checkout/cart");
            }
        } catch (\Exception $e) {
            $this->dataHelper->debugException($e);
            $this->messageManager->addErrorMessage(__("Payment exception"));
            $this->sageLogger->critical($e->getMessage());
            return $this->_redirect("checkout/cart");
        }
    }
}
