<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ranosys\CancelOrder\Block\Order;

/**
 * Sales order history block
 */
class History extends \Magento\Sales\Block\Order\History
{

    /**
     * @param \Magento\Framework\View\Element\Template\Context           $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Model\Session                            $customerSession
     * @param \Magento\Sales\Model\Order\Config                          $orderConfig
     * @param \Ranosys\CancelOrder\Helper\Data                           $helper
     * @param array                                                      $data
     */
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Ranosys\CancelOrder\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /*
     * Return order-cancellation URL
     */
    public function getFormAction($order)
    {
        return $this->getUrl('sales/order/cancel', ['order_id' => $order->getId(), '_secure' => true]);
    }

    /*
     * Return module status
     */
    public function getEnable()
    {
        return $this->helper->getEnable();
    }

    /*
     * Return custom notice text
     */
    public function getNotice()
    {
        return $this->helper->getNotice();
    }

    /*
     * Return button text
     */
    public function getLabel()
    {
        return $this->helper->getLabel();
    }

    public function getProductName($incrementId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($incrementId);
        $orderItems = $order->getAllItems();
        //print_r($orderItems);
        $itemQty = array();
        $productname = array();
        foreach ($orderItems as $item) {
             $itemQty[]=array('quantity'=>$item->getQtyOrdered(),'description'=>$item->getDescription(),'name'=>$item->getName(),'price'=>$item->getPrice());
            
            $productname[] = $item->getName();
        }
        //return $productname;
        return $productname;
    }
}
