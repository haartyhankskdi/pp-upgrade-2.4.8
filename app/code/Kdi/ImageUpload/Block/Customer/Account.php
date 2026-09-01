<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Block\Customer;

use Magento\Customer\Model\Session; 
use Kdi\ImageUpload\Model\EntityFactory;
use Magento\Sales\Model\OrderRepository;

class Account extends \Magento\Framework\View\Element\Template
{


    protected $_orderRepository;
    protected $session;
    protected $entityFactory;
    protected $orderFactory;


    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Session $session,
        EntityFactory $entityFactory,
        OrderRepository $orderRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->session = $session;
        $this->entityFactory = $entityFactory;
          $this->_orderRepository = $orderRepository;
         $this->orderFactory = $orderFactory;  
    }


    public function getCollection()
    {
       $customerId = $this->session ->getCustomer()->getId();
        $model =  $this->entityFactory->create();
        $collection = $model->getCollection();
        $data = $collection->addFieldToFilter('customer_id', ['eq' => $customerId]);
        $data->setOrder('created_at', 'DESC');
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/image.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" ========================================== " );
        $zendLogger->info(" collection data " );

        $zendLogger->info(print_r($data->getData(), true));

        return $data->getData();
    }

    public function getImageData(){

        $this->getCollection();

        $customerId = $this->session ->getCustomer()->getId();
        return $this->entityFactory->create()->load($customerId, 'customer_id');
    }


    public function getOrderDetails(){
        $data = $this->getImageData();

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/image.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" ========================================== " );
        $zendLogger->info(print_r($data->getOrderId(), true));

        print_r($data->getOrderId());
    }

    
    public function getOrderByIncrementId()
    {
        $orderIncrementId = 500000024;
        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        return $order->getId();
    }


    public function getProductDetailsByOrderIncrementId($orderId)
    {   


        $orderOBj =  $this->orderFactory->create()->loadByIncrementId($orderId);
        $order = $this->_orderRepository->get($orderOBj->getId());
        $productDetails = [];

        foreach ($order->getAllItems() as $item) {
            $productDetails[] = [
                'product_id' => $item->getProductId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice(),
                'quantity_ordered' => $item->getQtyOrdered(),
            ];
        }

        return $productDetails;
    }
}

