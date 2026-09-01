<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\Profile;

use Magenest\SagePay\Model\ProfileFactory;

class View extends \Magento\Framework\View\Element\Template
{
    protected $coreRegistry;

    protected $profileFactory;

    protected $orderFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ProfileFactory $profileFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data
    ) {
        $this->coreRegistry = $registry;
        $this->profileFactory = $profileFactory;
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    public function getProfile()
    {
        $profileId = $this->coreRegistry->registry('sagepay_profile_id');
        $profile = $this->profileFactory->create()->load($profileId);

        if ($profile) {
            return $profile;
        }

        return false;
    }

    /**
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    public function loadOrder($orderId)
    {
        return $this->orderFactory->create()->loadByIncrementId($orderId);
    }

    public function getOrderUrl($order)
    {
        if ($order instanceof \Magento\Sales\Model\Order) {
            $order = $order->getId();
        } else {
            $order = $this->orderFactory->create()->loadByIncrementId($order)->getId();
        }

        return $this->getUrl('sales/order/view', ['order_id' => $order]);
    }

    public function getCancelUrl($profile)
    {
        if ($profile instanceof \Magenest\SagePay\Model\Profile) {
            $profile = $profile->getId();
        }

        return $this->getUrl('sagepay/profile/cancel', ['profile_id' => $profile]);
    }

    public function getProduct()
    {
        $order = $this->getOrder();

        /** @var \Magento\Sales\Model\Order\Item $item */
        $item = $order->getItemsCollection()->setPageSize(1)->getFirstItem();

        $product = $item->getProduct();

        return $product;
    }

    public function getOrder()
    {
        $profileId = $this->coreRegistry->registry('sagepay_profile_id');
        $profile = $this->profileFactory->create()->load($profileId);

        $orderId = $profile->getOrderId();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);

        return $order;
    }
}
