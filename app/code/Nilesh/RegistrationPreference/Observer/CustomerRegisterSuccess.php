<?php

namespace Nilesh\RegistrationPreference\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session;

class CustomerRegisterSuccess implements ObserverInterface
{
    protected $storeManager;
    protected $responseFactory;
    protected $url;
      /**
     * @var Session
     */
    protected $session;

    public function __construct(
        StoreManagerInterface $storeManager,
        ResponseFactory $responseFactory,
        UrlInterface $url,
        Session $customerSession

    ) {
        $this->storeManager = $storeManager;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
         $this->session = $customerSession;
    }

    public function execute(Observer $observer)
    {

         $customer = $observer->getEvent()->getCustomer();


        // Get the current store ID
        $storeId = $this->storeManager->getStore()->getId();


        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" Store ID" . print_r($storeId, true));
            
        if ($storeId == 2) {
             $this->session->setCustomerDataAsLoggedIn($customer);
            $cartUrl = $this->url->getUrl('checkout/cart/');
            $this->responseFactory->create()->setRedirect($cartUrl)->sendResponse();
            exit();
        }
    }
}
