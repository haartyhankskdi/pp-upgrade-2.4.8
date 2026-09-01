<?php
 
namespace MY\BlacklistRepeat\Observer;
 
/**
 * Class OrderSaveAfter
 *
 * @package MY\BlacklistRepeat\Observer
 */
class BlacklistRepeatOrderPlace implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollectionFactory;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
 
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,        
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;        
        $this->orderCollectionFactory = $orderCollectionFactory;        
        $this->_resource = $resource;
        $this->customerSession = $customerSession;
        $this->customer = $customer;
        $this->customerCollectionFactory = $customerCollectionFactory;
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


        

        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();   
        $customerSession = $this->getCustomerBySession();

        $customerById = $this->getCustomerById($order->getCustomerId());
        $customerCollection = $this->getCustomerCollection($order->getCustomerId());


                     
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
        $customer   = $customerRepository->getById($order->getCustomerId());
        // $attribute  = $customer->getCustomAttribute('Status_flag');
        
        // // echo "<pre>";print_r($attribute);
        // $orderFlag = $customer->getCustomAttribute('Status_flag')->getValue();

        // // echo "Order Flag: ";
        // $orderFlag = $customer->getCustomAttribute('order_flag')->getValue();
        // $orderFlag = $customerSession || $customerById || $customerCollection;
        $orderFlag = $this->getCustomerBySession();
        $customerOrder = $this->orderCollectionFactory->create()->addFieldToFilter('customer_id', $order->getCustomerId());
        //echo "<pre>";print_r($customerOrder->getData());exit();
        $order_collection = $objectManager->create('Magento\Sales\Model\Order')->getCollection()->addAttributeToFilter('customer_id', $order->getCustomerId());
        foreach ($order_collection as $value) {                       
            $orderColl = $value->getAllVisibleItems();    
            foreach ($orderColl as $orderItem) {                 
               $orderItemFlag = $this->getProductHistoryCount($orderItem->getProductId(),$value['created_at'],$order->getCustomerId());
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

    public function getCustomerBySession(){
        $customer = $this->customerSession->getCustomer();
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/customer.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" Customer By Session " . print_r($customer->getData(), true));
        return $customer->getData('order_flag') ? $customer->getData('order_flag') : 0;
    }

    public function getCustomerById($customerId){
        $customer = $this->customer->load($customerId);
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/customer.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" customer by id " . print_r($customer->getData(), true));
       
        return $customer->getData('order_flag') ? $customer->getData('order_flag') : 0;
    }

    public function getCustomerCollection($customerId){
        $customer = $this->customerCollectionFactory->create()->addFieldToFilter('entity_id', $customerId);
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/customer.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" customer collection " . print_r($customer->getData(), true));
        return $customer->getData('order_flag') ? $customer->getData('order_flag') : 0;
        
    }





}