<?php
/**
 * Created by ducanh
 * Developed by ducanh
 * Date: 26/04/2019
 * Time: 15:33
 */

namespace Magenest\SagePay\Plugin;

use Magento\Sales\Model\Order;

class ConfirmationMail
{
    public function aroundSend(
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Closure $proceed,
        \Magento\Sales\Model\Order $order,
        $forceSyncMode = false
    ) {
        $returnValue = null;
        $paymentMethod = $order->getPayment()->getData('method');
        $orderStatus = $order->getData('status');
        if (strpos($paymentMethod, 'magenest') !== false && $orderStatus == "pending") {
            $returnValue = null;
        } else {
            $returnValue = $proceed($order, $forceSyncMode);
        }
        return $returnValue;
    }
}
