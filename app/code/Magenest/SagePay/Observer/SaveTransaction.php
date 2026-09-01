<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveTransaction implements ObserverInterface
{
    protected $transactionFactory;
    protected $sageLog;
    protected $_serialize;

    public function __construct(
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magenest\SagePay\Model\TransactionFactory $transactionFactory,
        \Magenest\SagePay\Helper\Logger $sageLog
    ) {
        $this->_serialize = $serialize;
        $this->transactionFactory = $transactionFactory;
        $this->sageLog = $sageLog;
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
            $order = $observer->getOrder();
            $orderId = $order->getId();
            $payment = $order->getPayment();
            $methodName = $payment->getMethod();
            if (str_contains($methodName, "magenest_sagepay")) {
                $transactionId = $payment->getAdditionalInformation('sagepay_transaction_id');
                if (!empty($payment->getAdditionalInformation('sagepay_response'))) {
                    $response = $this->_serialize->unserialize($payment->getAdditionalInformation('sagepay_response'));
                }
                if ($transactionId || isset($response['VendorTxCode']) || isset($response['vendorTxCode'])) {
                    $transactionModel = $this->transactionFactory->create();
                    if ($transactionId) {
                        $transactionModel = $transactionModel->load($transactionId, "transaction_id");
                    }
                    if (isset($response['VendorTxCode'])) {
                        $transactionModel->load($response['VendorTxCode'], "vendor_tx_code");
                    } elseif (isset($response['vendorTxCode'])) {
                        $transactionModel->load($response['vendorTxCode'], "vendor_tx_code");
                    }
                    if (!$transactionModel->getTransactionId()) {
                        $transactionModel->setData("transaction_id", $transactionId);
                        $transactionModel->setData("order_id", $orderId);
                    }
                    $responseData = isset($response) ? json_decode(json_encode($response), true) : [];
                    if (is_array($responseData)) {
                        $response = $this->removeUnlessData($responseData, 'array');
                    }
                    $cardSecure = isset($response['3DSecure']) ? (array) $response['3DSecure'] : [];

                    $data = [
                        'order_id' => $orderId,
                        'transaction_type' => isset($response['transactionType']) ? $response['transactionType'] : null,
                        'transaction_status' => isset($response['status']) ? $response['status'] : null,
                        'card_secure' => isset($cardSecure['status']) ? $cardSecure['status'] : null,
                        'status_detail' => isset($response['statusDetail']) ? $response['statusDetail'] : null,
                        'customer_id' => $order->getCustomerId(),
                        'customer_email' => $order->getCustomerEmail(),
                        'quote_id' => $order->getQuoteId(),
                        'is_subscription' => 0,
                    ];
                    if (!$transactionModel['response_data']) {
                        $transactionModel->setData("response_data", json_encode($responseData));
                    }
                    $this->removeUnlessData($data);
                    $transactionModel->addData($data);
                    if ($response['vendorTxCode'] ?? false) {
                        $transactionModel->setData('vendor_tx_code', $response['vendorTxCode']);
                    }
                    if ($transactionModel->getData() != $transactionModel->getOrigData()) {
                        $transactionModel->save();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->sageLog->critical($e->getMessage());
        }
    }

    /**
     * @param $data
     * @param string $type
     * @return mixed
     */
    private function removeUnlessData($data, $type = 'boolean')
    {
        foreach ($data as $k => $v) {
            if ($type == 'array') {
                if (is_array($v)) {
                    unset($data[$k]);
                }
            } else {
                if (!$v) {
                    unset($data[$k]);
                }
            }
        }
        return $data;
    }
}
