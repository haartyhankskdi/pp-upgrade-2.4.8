<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magenest\SagePay\Model\ResourceModel\Plan\CollectionFactory;

class PlanDataProvider extends AbstractModifier
{
    protected $_locator;

    protected $_request;

    protected $_logger;

    protected $_planFactory;

    protected $_serialize;

    /**
     * PlanDataProvider constructor.
     * @param RequestInterface $request
     * @param LocatorInterface $locator
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param CollectionFactory $planFactory
     */
    public function __construct(
        RequestInterface $request,
        LocatorInterface $locator,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        CollectionFactory $planFactory
    ) {
        $this->_serialize = $serialize;
        $this->_planFactory = $planFactory;
        $this->_logger = $loggerInterface;
        $this->_request = $request;
        $this->_locator = $locator;
    }

    public function modifyData(array $data)
    {
        $product = $this->_locator->getProduct();
        $productId = $product->getId();

        $plan = $this->_planFactory->create()
            ->addFieldToFilter('product_id', $productId)
            ->setPageSize(1)
            ->getFirstItem();
        if ($plan->getId()) {
            $isEnabled = $plan->getEnabled();

            if (!empty($plan->getSubscriptionValue())) {
                $options = $this->_serialize->unserialize($plan->getSubscriptionValue());
            } else {
                $options = [];
            }

            $data[(string)$productId]['event']['magenest_sagepay_enabled']['enable'] = $isEnabled;

            $data[(string)$productId]['event']['magenest_sagepay']['subscription_options']
            ['subscription_options'] = $options;
        }

        return $data;
    }

    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
