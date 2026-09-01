<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Observer\Product;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magenest\SagePay\Model\ResourceModel\Plan\CollectionFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use Psr\Log\LoggerInterface;

class Save implements ObserverInterface
{
    protected $_logger;

    protected $_request;

    protected $messageManager;

    protected $_serialize;
    /**
     * @var CollectionFactory
     */
    private $_planCollectionFactory;

    /**
     * Save constructor.
     * @param LoggerInterface $loggerInterface
     * @param RequestInterface $requestInterface
     * @param CollectionFactory $planCollectionFactory
     * @param Serialize $serialize
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        LoggerInterface $loggerInterface,
        RequestInterface $requestInterface,
        CollectionFactory $planCollectionFactory,
        Serialize $serialize,
        ManagerInterface $messageManager
    ) {
        $this->_serialize = $serialize;
        $this->_logger = $loggerInterface;
        $this->_request = $requestInterface;
        $this->_planCollectionFactory = $planCollectionFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $planModel = $this->_planCollectionFactory->create();
            $data = $this->_request->getParams();

            $product = $observer->getProduct();
            $productId = $product->getId();

            $plan = $planModel->addFieldToFilter('product_id', $productId)
                ->setPageSize(1)
                ->getFirstItem();

            if (!isset($data['event'])) {
                return;
            }
            $data = $data['event'];
            $result = [];

            if (array_key_exists('magenest_sagepay', $data)) {
                $newData = $data['magenest_sagepay']['subscription_options']['subscription_options'];

                if ($newData != 'false') {
                    $result = $this->pushData($newData, $result);
                }
            }

            $plan->setData("enabled", $data['magenest_sagepay_enabled']['enable']);
            $plan->setData("subscription_value", $this->_serialize->serialize($result));
            $plan->setData("product_id", $productId);
            $plan->save();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("Cannot save Sagepay product"));
        }
    }

    /**
     * @param $newData
     * @param $result
     * @return mixed
     */
    private function pushData($newData, $result)
    {
        foreach ($newData as $item) {
            if (array_key_exists('is_delete', $item)) {
                if ($item['is_delete']) {
                    continue;
                }
            }
            array_push($result, $item);
        }
        return $result;
    }
}
