<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Customer;

use Magento\Catalog\Block\Product\Context;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Sales\Model\OrderFactory;

class Card extends \Magento\Framework\View\Element\Template
{
    protected $_currentCustomer;

    protected $_orderFactory;

    protected $_cardFactory;

    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        \Magenest\SagePay\Model\CardFactory $cardFactory,
        OrderFactory $orderFactory,
        array $data
    ) {
        $this->_currentCustomer = $currentCustomer;
        $this->_cardFactory = $cardFactory;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    public function getDataCard()
    {
        $customerId = $this->_currentCustomer->getCustomerId();

        return $this->_cardFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder("created_at", "DESC");
    }

    public function getOrderViewUrl($incrementId)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $this->getOrderId($incrementId)]);
    }

    public function getDelUrl($id)
    {
        return $this->getUrl('sagepay/card/delete', ['id' => $id]);
    }
}
