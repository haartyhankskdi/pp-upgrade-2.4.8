<?php
/**
 * Created by ducanh
 * Developed by ducanh
 * Date: 14/05/2019
 * Time: 13:54
 */

namespace Magenest\SagePay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SendConfirmationMail implements ObserverInterface
{

    protected $orderSender;

    public function __construct(
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    ) {
        $this->orderSender = $orderSender;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order instanceof \Magento\Framework\Model\AbstractModel) {
            if ($order->getState() == 'processing') {
                $this->orderSender->send($order);
            }
        }
        return $this;
    }
}
