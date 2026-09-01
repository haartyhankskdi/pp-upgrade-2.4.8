<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Customer;

use Magenest\SagePay\Model\ProfileFactory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Sales\Model\OrderFactory;

class View extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;

    protected $_profileFactory;

    protected $_orderFactory;

    public function __construct(
        Context $context,
        ProfileFactory $profileFactory,
        OrderFactory $orderFactory,
        array $data
    ) {
        $this->_coreRegistry = $context->getRegistry();
        $this->_profileFactory = $profileFactory;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magenest\SagePay\Model\Profile
     */
    public function getProfile()
    {
        $id = $this->_coreRegistry->registry('sagepay_profile_customer_id');
        $profile = $this->_profileFactory->create()->load($id);

        return $profile;
    }

    public function getOrder()
    {
        $profileId = $this->_coreRegistry->registry('sagepay_profile_customer_id');
        $profile = $this->_profileFactory->create()->load($profileId);

        $orderId = $profile->getOrderId();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);

        return $order;
    }

    public function getProduct()
    {
        $order = $this->getOrder();

        /** @var \Magento\Sales\Model\Order\Item $item */
        $item = $order->getItemsCollection()->setPageSize(1)->getFirstItem();

        $product = $item->getProduct();

        return $product;
    }

    /**
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    public function loadOrder($orderId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($orderId);
    }

    public function getOrderUrl($order)
    {
        if ($order instanceof \Magento\Sales\Model\Order) {
            $order = $order->getId();
        } else {
            $order = $this->_orderFactory->create()->loadByIncrementId($order)->getId();
        }

        return $this->getUrl('sales/order/view', ['order_id' => $order]);
    }

    public function getCancelUrl($profileId)
    {
        return $this->getUrl(
            'sagepay/customer/cancel',
            [
                'profile_id' => $profileId
            ]
        );
    }
}
