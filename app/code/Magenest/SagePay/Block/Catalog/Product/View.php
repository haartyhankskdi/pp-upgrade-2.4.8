<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Catalog\Product;

use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magenest\SagePay\Model\ResourceModel\Plan\CollectionFactory;

class View extends \Magento\Catalog\Block\Product\AbstractProduct
{
    protected $_date;

    protected $_planFactory;

    protected $_serialize;

    public function __construct(
        Context $context,
        DateTime $dateTime,
        CollectionFactory $planFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        array $data = []
    ) {
        $this->_serialize = $serialize;
        $this->_date = $dateTime;
        $this->_planFactory = $planFactory;
        parent::__construct($context, $data);
    }

    public function getIsSubscriptionProduct()
    {
        $product = $this->_coreRegistry->registry('current_product');
        $productId = $product->getId();

        $plan = $this->_planFactory->create()->addFieldToFilter('product_id', $productId)
            ->setPageSize(1)
            ->getFirstItem();

        if ($plan) {
            $value = $plan->getEnabled();

            return $value;
        }

        return false;
    }

    /**
     * @return array|bool|float|int|mixed|string
     */
    public function getSubscriptionOptions()
    {
        $product = $this->_coreRegistry->registry('current_product');
        $productId = $product->getId();

        $plan = $this->_planFactory->create()->addFieldToFilter('product_id', $productId)
            ->setPageSize(1)
            ->getFirstItem();

        if ($plan) {
            if (!empty($plan->getSubscriptionValue())) {
                $options = $this->_serialize->unserialize($plan->getSubscriptionValue());
            } else {
                $options = [];
            }
            return $options;
        }

        return [];
    }
}
