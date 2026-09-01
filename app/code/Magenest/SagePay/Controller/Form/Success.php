<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Form;

use Magenest\SagepayLib\Classes\SagepayApiException;

class Success extends \Magenest\SagePay\Controller\Success
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $crypt = $this->getRequest()->getParam('crypt');
            $response = $this->sageHelper->decryptResp($crypt);
            $decryptResponse = $response['decrypt'] ?? [];

            $status = $decryptResponse['Status'] ?? '';
            $statusDetail = $decryptResponse['StatusDetail'] ?? '';
            $vendorTxCode = $decryptResponse['VendorTxCode'] ?? '';
            $cardSecure = $decryptResponse['3DSecureStatus'] ?? '';
            $transactionId = $decryptResponse['VPSTxId'] ?? "";
            $transactionId2 = str_replace(["{", "}"], "", $transactionId);
            $checkOrder = $this->_transactionCollectionFactory->create()
                ->addFieldToFilter('transaction_id', $transactionId2)->getData();
            if (isset($checkOrder) && count($checkOrder) > 0) {
                return $this->_redirect("checkout/onepage/success");
            } else {
                $transaction = $this->_transactionCollectionFactory->create()
                    ->addFieldToFilter('vendor_tx_code', $vendorTxCode)->getLastItem();
                $quote = $this->quoteFactory->create()->load($transaction->getQuoteId());

                $this->_eventManager->dispatch(
                    "magenest_sagepay_save_transaction",
                    ['transaction_data' => $this->sageHelper->getResponseData($decryptResponse, $quote, "Form")]
                );
                $quote->getPayment()->setAdditionalInformation("sagepay_transaction_id", $transactionId2);
                if (!$this->customerSession->isLoggedIn()) {
                    $quote->setCheckoutMethod(\Magento\Quote\Model\QuoteManagement::METHOD_GUEST);
                }
                $this->cartRepository->save($quote);
                $this->sageLogger->debug(var_export($response, true));
                //set checkout session load inactive quote
                $payment = $quote->getPayment();
                if ($status == "OK") {
                    //create order
                    $quote->getPayment()->setMethod('magenest_sagepay_form');
                    $data = [
                        'transactionType' => 'Form',
                        'status' => $status,
                        'card_secure' => $cardSecure,
                        'vendorTxCode' => $vendorTxCode,
                        'statusDetail' => $statusDetail
                    ];
                    $payment->setAdditionalInformation(
                        "transaction_details",
                        $this->_serialize->serialize($decryptResponse)
                    );
                    $payment->setAdditionalInformation("sagepay_response", $this->_serialize->serialize($data));
                    $quote->setIsActive(true);
                    $this->cartRepository->save($quote);
                    $orderId = $this->quoteManagement->placeOrder($quote->getId(), $payment);
                    $order = $this->orderFactory->create()->load($orderId);
                    $payment = $order->getPayment();
                    $order->addStatusHistoryComment($statusDetail);
                    $payment->setIsTransactionClosed(0);
                    $payment->setShouldCloseParentTransaction(0);
                    $order->save();
                    $increment_id = $order->getRealOrderId();
                    $this->messageManager->addSuccessMessage("Your order (ID: $increment_id) was successful!");
                    $this->messageManager->addSuccessMessage($status . " - " . $statusDetail);
                    $this->_checkoutSession->setTransctionId($transactionId2);
                    return $this->_redirect("checkout/onepage/success");
                } else {
                    $e = new SagepayApiException($status . " - " . $statusDetail);
                    $this->dataHelper->debugException($e);
                    $this->messageManager->addErrorMessage($e->getMessage());
                    $this->sageLogger->critical($e->getMessage());
                    return $this->_redirect("checkout/cart");
                }
            }
        } catch (\Exception $e) {
            $this->dataHelper->debugException($e);
            $this->messageManager->addErrorMessage(__("Payment exception"));
            $this->sageLogger->critical($e->getMessage());
            return $this->_redirect("checkout/cart");
        }
    }
}
