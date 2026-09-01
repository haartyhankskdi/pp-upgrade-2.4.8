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
    protected $orderRepository;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->orderCollection = $orderCollection;
        $this->registry = $registry;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
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

        $collection = $this->orderCollection->create()->addAttributeToSelect('entity_id')
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
           foreach ($collection->getData() as $key => $value) {
               // code...
            $OrderIdarray[] = $value['entity_id'];//exit();
           }
           return $OrderIdarray;
           //print_r($collection->getData());
           // if(count($collection->getData()) > 1){
           //  return true;
           // }
           // return false;
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
        $abc[] = $_item->getSku();
        
    }
    return $abc;
    }
    public function getCustomerFlag()
    {
       $customerId = $this->registry->registry('current_order')->getCustomerId();
       $customer = $this->customerRepository->getById($customerId);
       if($customer->getCustomAttribute('order_flag')){
       $orderFlag = $customer->getCustomAttribute('order_flag')->getValue();
       return $orderFlag;
       }
    }

    public function getOrderData($order_id)
    {
       try {
          $order = $this->orderRepository->get($order_id);
          foreach ($order->getAllVisibleItems() as $_item) {
            $abc[] = trim($_item->getSku());
            }
       } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
          throw new \Magento\Framework\Exception\LocalizedException(__('This order no longer exists.'));
       }
       return $abc;
    }

}

