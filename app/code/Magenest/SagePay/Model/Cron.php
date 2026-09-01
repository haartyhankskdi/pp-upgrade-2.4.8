<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magenest\SagePay\Model\ProfileFactory;
use Magenest\SagePay\Helper\Data;
use Psr\Log\LoggerInterface;
use Magenest\SagePay\Model\TransactionFactory;

class Cron
{
    protected $_date;

    protected $_profileFactory;

    protected $_helper;

    protected $_logger;

    protected $_transFactory;

    public function __construct(
        DateTime $dateTime,
        ProfileFactory $profileFactory,
        Data $helper,
        LoggerInterface $loggerInterface,
        TransactionFactory $transactionFactory
    ) {
        $this->_date = $dateTime;
        $this->_profileFactory = $profileFactory;
        $this->_helper = $helper;
        $this->_logger = $loggerInterface;
        $this->_transFactory = $transactionFactory;
    }

    public function daily()
    {
        $today = $this->_date->gmtDate('Y-m-d');

        $profileModel = $this->_profileFactory->create();
        $profiles = $profileModel->getCollection();

        $url = $this->_helper->getPiEndpointUrl();
        $url .= '/transactions';

        foreach ($profiles as $profile) {
            /** @var \Magenest\SagePay\Model\Profile $profile */
            $nextBilling = $profile->getData('next_billing');
            if ($today == $nextBilling) {
                $transactionId = $profile->getData('transaction_id');
                $amount = intval($profile->getData('amount'));
                $currency = $profile->getData('currency');
                $remaining_cycles = $profile->getData('remaining_cycles');
                $status = $profile->getData('status');

                if (($remaining_cycles == -1 || $remaining_cycles > 0) && $status == 'Active') {
                    $payload = '{' .
                        '"transactionType": "Repeat",' .
                        '"referenceTransactionId": "' . $transactionId . '",' .
                        '"vendorTxCode": "' . $this->_helper->getVendorCode() . time() . '",' .
                        '"amount": ' . $amount . ',' .
                        '"currency": ' . $currency . ',' .
                        '"description": "Demo transaction"' .
                        '}';

                    $response = $this->_helper->sendRequest($url, $payload);

//                $this->_logger->addDebug(print_r($response, true));

                    if ($response['statusCode'] == 0000) {
                        /** @var \Magenest\SagePay\Model\Transaction $transModel */
                        $transModel = $this->_transFactory->create();

                        /** @var \Magento\Sales\Model\Order $order */
                        /** @var \Magenest\SagePay\Model\Profile $profile */
                        $order = $profile->placeOrder();

                        $payment = $order->getPayment();
                        $payment->setTransactionId($profile->getData('transaction_id'))
                            ->setIsTransactionClosed(0);

                        $order->save();

                        //the gross amount
                        $grossAmount = $order->getGrandTotal();

                        $payment->registerCaptureNotification($grossAmount);
                        $order->save();

                        $invoice = $payment->getCreatedInvoice();
                        if ($invoice) {
                            // notify customer
                            $message = __('Notified customer about invoice #%s.', $invoice->getIncrementId());
                            $order->queueNewOrderEmail()->addStatusHistoryComment($message)
                                ->setIsCustomerNotified(true)
                                ->save();
                        }

                        $data = [
                            'transaction_id' => $response['transactionId'],
                            'transaction_type' => $response['transactionType'],
                            'order_id' => $order->getIncrementId(),
                            'is_subscription' => 0
                        ];

                        $transModel->setData($data)->save();

                        if ($remaining_cycles > 0) {
                            $remaining_cycles--;
                            $nextBilling = date('Y-m-d', strtotime($today . '+' . $profile->getFrequency()));
                            $profile->addData([
                                'remaining_cycles' => $remaining_cycles,
                                'last_billed' => $today,
                                'next_billing' => $nextBilling
                            ])->save();
                        }
                    }
                } else {
                    $profile->addData(['status' => 'Expired'])->save();
                }
            }
        }
    }
}
