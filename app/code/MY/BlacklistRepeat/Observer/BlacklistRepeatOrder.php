<?php
 
namespace MY\BlacklistRepeat\Observer;
 
/**
 * Class OrderSaveAfter
 *
 * @package MY\BlacklistRepeat\Observer
 */
class BlacklistRepeatOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
 
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,        
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;        
        $this->orderCollectionFactory = $orderCollectionFactory;        
        $this->_resource = $resource;
    }
 
    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) 
    {
        $customer = $observer->getCustomer();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
        $customer = $customerRepository->getById($customer->getId());
        $orderFlag = $customer->getCustomAttribute('order_flag')->getValue();
        $customerOrder = $this->orderCollectionFactory->create()->addFieldToFilter('customer_id', $customer->getId());
        //echo "<pre>";print_r($customerOrder->getData());exit();
        $order_collection = $objectManager->create('Magento\Sales\Model\Order')->getCollection()->addAttributeToFilter('customer_id', $customer->getId());
        foreach ($order_collection as $value) {                       
            $orderColl = $value->getAllVisibleItems();    
            foreach ($orderColl as $orderItem) {                 
               $orderItemFlag = $this->getProductHistoryCount($orderItem->getProductId(),$value['created_at'],$customer->getId());
               $orders = $objectManager->create('Magento\Sales\Model\Order')->load($value['entity_id']);
               if($orderItemFlag && $orderFlag){
                    $orders->setOrderStatus(3);
               }
               elseif($orderItemFlag && !$orderFlag){
                    $orders->setOrderStatus(2);
               }
               elseif(!$orderItemFlag && $orderFlag){
                    $orders->setOrderStatus(1);
               }else{
                    $orders->setOrderStatus(0);
               }
               $orders->save();
            }                     
        }
    }

    public function getProductHistoryCount($productId, $order_created_at, $customerId)
    {
        $connection = $this->_resource->getConnection();
        $maxDays=date('t');
        $prev_date = date('Y-m-d', strtotime('-'.$maxDays.' days'));
        $query = "SELECT soi.product_id FROM `sales_order_item` AS soi LEFT JOIN sales_order AS so ON so.entity_id = soi.order_id WHERE so.customer_id = '$customerId' AND so.created_at <= '$order_created_at' AND so.created_at >= '$prev_date' AND soi.product_id = '$productId'";
        $result = $connection->fetchAll($query);
        return count($result) > 1;
    }
}