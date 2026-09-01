<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\OrderFlag\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class OrderFlag extends AbstractHelper
{
    protected $orderCollection;
    protected $registry;
    protected $customerRepository;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->orderCollection = $orderCollection;
        $this->registry = $registry;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    public function getOrderIdFlag($customerId)
    {
        // code...
        $maxDays=date('t');
        $prev_date = date('Y-m-d', strtotime('-'.$maxDays.' days'));

        // $collection = $orderCollection->create()->addAttributeToSelect('entity_id')
        //    ->addAttributeToFilter('status', ['in' => array('pending,processing,complete')])
        //    ->addAttributeToFilter('created_at', ['gteq'=>$prev_date.' 00:00:00']);

        $collection = $orderCollection->create()->addAttributeToSelect('entity_id')
           ->addAttributeToFilter('created_at', ['gteq'=>$prev_date.' 00:00:00'])
           ->addAttributeToFilter('customer_id', ['eq'=> $customerId])
           ->addFieldToFilter(
                                ['status', 'status'],
                                [
                                    ['eq' => 'pending'],
                                    ['eq' => 'processing'],
                                    ['eq' => 'complete'],
                                ]
                            );
           //echo $collection->getSelect();
           // foreach ($collection->getData() as $key => $value) {
           //     // code...
           //  echo $value['entity_id'];exit();
           // }
           //print_r($collection->getData());
           if(count($collection->getData()) > 1){
            return true;
           }
           return false;
    }

    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    public function getProductId()
    {
        $order = $this->registry->registry('current_order');
        // code...
        $abc =array();
        foreach ($order->getAllVisibleItems() as $_item) {
        $abc[] = $_item->getId();

    }
    return $abc;
    }
    public function getCustomerFlag()
    {
       $customerId = $this->registry->registry('current_order')->getCustomerId();
       if($customerId === null){
        return;
       }
       $customer = $this->customerRepository->getById($customerId);
       if($customer->getCustomAttribute('order_flag')){
       $orderFlag = $customer->getCustomAttribute('order_flag')->getValue();
       return $orderFlag;
       }
    }

    public function getCustomerFlagForCustomerEdit($customerId)
    {
       $customer = $this->customerRepository->getById($customerId);
       if($customer->getCustomAttribute('order_flag')){
       $orderFlag = $customer->getCustomAttribute('order_flag')->getValue();
       return $orderFlag;
       }
    }
    
    public function getProductHistory($productId, $order_created_at)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceCollection = $objectManager->create('Magento\Framework\App\ResourceConnection');
        $connection = $resourceCollection->getConnection();
        $customerId = $this->registry->registry('current_order')->getCustomerId();
        //$table = $connection->getTableName('my_custom_table');
        //$maxDays=date('t'); // days in a month
        $maxDays = 56;
        $prev_date = date('Y-m-d', strtotime('-'.$maxDays.' days'));
        $query = "SELECT soi.product_id FROM `sales_order_item` AS soi LEFT JOIN sales_order AS so ON so.entity_id = soi.order_id WHERE so.customer_id = '$customerId' AND so.created_at <= '$order_created_at' AND so.created_at >= '$prev_date' AND soi.product_id = '$productId'";
        $result = $connection->fetchAll($query);
        return count($result) > 1;
    }
}

