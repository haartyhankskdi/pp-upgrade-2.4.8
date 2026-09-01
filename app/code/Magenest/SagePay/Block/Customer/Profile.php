<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Customer;

use Magenest\SagePay\Model\ProfileFactory;
use Magento\Catalog\Block\Product\Context;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Sales\Model\OrderFactory;

class Profile extends \Magento\Framework\View\Element\Template
{
    protected $_currentCustomer;

    protected $_profileFactory;

    protected $_orderFactory;

    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        ProfileFactory $profileFactory,
        OrderFactory $orderFactory,
        array $data
    ) {
        $this->_currentCustomer = $currentCustomer;
        $this->_profileFactory = $profileFactory;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    public function getCustomerProfiles()
    {
        $customerId = $this->_currentCustomer->getCustomerId();

        $profiles = $this->_profileFactory->create()->getCollection()->addFieldToFilter('customer_id', $customerId);

        return $profiles;
    }

    public function getOrderId($incrementId)
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($incrementId);

        return $order->getId();
    }

    public function getOrderViewUrl($incrementId)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $this->getOrderId($incrementId)]);
    }

    public function getProfileViewUrl($id)
    {
        return $this->getUrl('sagepay/customer/view', ['id' => $id]);
    }

    public function getCcLast4($orderId)
    {
        /** @var \Magento\Sales\Model\Order $orderModel */
        $orderModel = $this->_orderFactory->create();
        $orderModel->loadByIncrementId($orderId);

        return $orderModel->getPayment()->getCcLast4();
    }
}
