<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveTransactionEvent implements ObserverInterface
{
    /**
     * @var \Magenest\SagePay\Helper\Logger
     */
    private $sageLogger;
    /**
     * @var \Magenest\SagePay\Model\Transaction
     */
    private $transaction;

    /**
     * SaveTransactionEvent constructor.
     * @param \Magenest\SagePay\Model\Transaction $transaction
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     */
    public function __construct(
        \Magenest\SagePay\Model\Transaction $transaction,
        \Magenest\SagePay\Helper\Logger $sageLogger
    ) {
        $this->transaction = $transaction;
        $this->sageLogger = $sageLogger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         * @var \Magento\Sales\Model\Order\Payment $payment
         */
        try {
            $transactionData = $observer->getTransactionData();
            $transactionId = isset($transactionData['transaction_id']) ? $transactionData['transaction_id'] : false;
            $vendorTxCode = isset($transactionData['vendor_tx_code']) ? $transactionData['vendor_tx_code'] : false;
            if ($transactionId) {
                $transactionModel = $this->transaction->load($transactionId, "transaction_id");
            }
            if ($vendorTxCode) {
                $transactionModel = $this->transaction->load($vendorTxCode, "vendor_tx_code");
            }
            if (isset($transactionModel)) {
                if (!$transactionModel->getId() || $transactionModel->getTransactionId() == '') {
                    $transactionModel->setData("transaction_id", $transactionId);
                }
                $transactionModel->addData($transactionData);
                if ($transactionModel->getData() != $transactionModel->getOrigData()) {
                    $transactionModel->save();
                }
            }
        } catch (\Exception $e) {
            $this->sageLogger->critical($e->getMessage());
        }
    }
}
