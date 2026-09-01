<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\OrderStatus\Observer\Backend\Sales;

use Psr\Log\LoggerInterface;
use Nilesh\OrderStatus\Helper\Mail;

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{

    protected $_logger;
    protected $_helperMail;

    public function __construct(
        LoggerInterface $loggerInterface,
        Mail $helperMail
    ) {
        $this->_logger = $loggerInterface;
        $this->_helperMail = $helperMail;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
         /* @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        $stateProcessing = $order::STATE_PROCESSING;
        // Only trigger when an order enters processing state.
        if ($order->getState() == $stateProcessing && $order->getOrigData('state') == $stateProcessing) {
            // $this->_logger->addDebug($order->getCustomerName());
            // $this->_logger->addDebug($order->getCustomerEmail());
            // $this->_logger->addDebug("STATE".$order->getState());
            // $this->_logger->addDebug("STATUS".$order->getStatus());
            // $this->_logger->addDebug("ORGISTATE".$order->getOrigData('state'));
            // $this->_logger->addDebug("ORGISTATUS".$order->getOrigData('status'));
            // $this->_logger->addDebug($this->_helperMail->getSenderEmail("sales_email/custom_approve_status/identity")['email']);
            // $this->_logger->addDebug($this->_helperMail->getSenderEmail("sales_email/custom_approve_status/identity")['name']);
            $orderEmail = $order->getCustomerEmail();
            $orderVar = array('custName' => $order->getCustomerName() );
            if($order->getStatus('status') == "disapprove"){
                $this->_helperMail->sendDisapproveTemplateEmail($orderEmail,$orderVar);
            }elseif($order->getStatus('status') == "approve"){
                $this->_helperMail->sendApproveTemplateEmail($orderEmail,$orderVar);
            }
        }
    }
}

