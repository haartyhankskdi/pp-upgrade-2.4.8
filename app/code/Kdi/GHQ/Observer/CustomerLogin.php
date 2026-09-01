<?php

namespace Kdi\GHQ\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        echo "Customer LoggedIn";
        $customer = $observer->getEvent()->getCustomer();
        echo $customer->getName(); //Get customer name

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/ghq.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" Customer Data " . print_r($customer->getData(), true));
        exit;
    }
}