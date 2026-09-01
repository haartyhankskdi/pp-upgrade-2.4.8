<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Paypal;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;

class Redirect extends Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * Redirect constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\OrderRepository $orderRepository
    ) {
        $this->checkoutSession = $session;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            $order->setPaypalTransactionId($order->getPayment()->getAdditionalInformation('transaction_id'));
            $this->orderRepository->save($order);
            if ($order->getState() == Order::STATE_NEW) {
                $redirectUrl = $order->getPayment()->getAdditionalInformation('paypal_redirect_url');
                if ($redirectUrl) {
                    return $this->_redirect($redirectUrl);
                }
            } else {
                return $this->_redirect('sales/order/history');
            }
        } catch (\Exception $exception) {
            $this->messageManager->addError(__('Redirect Error'));
        }
        return $this->_redirect("checkout/onepage/success");
    }
}
