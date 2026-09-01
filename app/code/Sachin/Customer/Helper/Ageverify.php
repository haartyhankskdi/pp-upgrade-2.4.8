<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Ageverify extends AbstractHelper
{

	protected $customerSession;
    protected $_catalogSession;
    protected $ageFactory;
	protected $customerRepository;
    protected $product;
    protected $cart;

    const XML_PATH_AGEVERIFICATION = 'ageverification/';
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Model\Session $catalogSession,
        \Sachin\Customer\Model\AgeverificationFactory $ageFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Model\Product $product,
        \Magento\Checkout\Model\Cart $cart
    ) {
        parent::__construct($context);
    	$this->customerSession = $customerSession;
        $this->_catalogSession = $catalogSession;
        $this->ageFactory = $ageFactory;
    	$this->customerRepository = $customerRepository;
        $this->product = $product;
        $this->cart = $cart;
    }

    public function getAge(){ 
        if($this->customerSession->isLoggedIn()){
            
    	$customerId = $this->customerSession->getCustomer()->getId();
    	$customer = $this->customerRepository->getById($customerId);
        if($customer->getCustomAttribute('ageverification')){
		$cattrValue = $customer->getCustomAttribute('ageverification')->getValue();
        return $cattrValue;
        }
        }  	
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }
    
    public function getGeneralConfig($code, $storeId = null)
    {

        return $this->getConfigValue(self::XML_PATH_AGEVERIFICATION .'credential/'. $code, $storeId);
    }

    public function saveCustomerAgeverify($data)
    {
            $ageFactory = $this->ageFactory->create();
            $customerid = $this->customerSession->getCustomer()->getId();
            $newsModel = $ageFactory->getCollection()->addFieldToFilter('customer_id', $customerid);
            $newsModel = $newsModel->getData();
            if(!empty($data['ageverification_id'])){
                //To update record
            $ageFactory->load($data['ageverification_id']);
            //$ageFactory->setCustomerId($customerid);
            $ageFactory->setFirstname($data['firstname']);
            $ageFactory->setLastname($data['lastname']);
            $ageFactory->setGender($data['gender']);
            $ageFactory->setDob($data['dob']);
            $ageFactory->setStreet($data['address1']);
            $ageFactory->setCity($data['address2']);
            $ageFactory->setPostcode($data['postcode']);
            $ageFactory->save();
            }else{
                //To Add new record
            $ageFactory->setCustomerId($customerid);
            $ageFactory->setFirstname($data['firstname']);
            $ageFactory->setLastname($data['lastname']);
            $ageFactory->setGender($data['gender']);
            $ageFactory->setDob($data['dob']);
            $ageFactory->setStreet($data['address1']);
            $ageFactory->setCity($data['address2']);
            $ageFactory->setPostcode($data['postcode']);
            $ageFactory->save();
            }
            //return true;
    }

    public function updateCustomerAgeverify($data)
    {
        $ageFactory = $this->ageFactory->create();
            //$customerid = $this->customerSession->getCustomer()->getId();
            //$newsModel = $ageFactory->getCollection()->addFieldToFilter('customer_id', $customerid);
            //$newsModel = $newsModel->getData();
            if(!empty($data['ageverification_id'])){
                //To update record
            $ageFactory->load($data['ageverification_id']);
            //$ageFactory->setCustomerId($customerid);
            $ageFactory->setFirstname($data['firstname']);
            $ageFactory->setLastname($data['lastname']);
            $ageFactory->setGender($data['gender']);
            $ageFactory->setDob($data['dob']);
            $ageFactory->setStreet($data['address1']);
            $ageFactory->setCity($data['address2']);
            $ageFactory->setPostcode($data['postcode']);
            $ageFactory->save();
            }
    }
    public function getAgeverifyCustomer()
    {
            $ageFactory = $this->ageFactory->create();
            $customerid = $this->customerSession->getCustomer()->getId();
            $newsModel = $ageFactory->getCollection()->addFieldToFilter('customer_id', $customerid);
            $newsModel = $newsModel->getData();
            if(isset($newsModel[0])){
            return $newsModel[0];
        }else{
            return false;
        }
    }

    public function removeCartProduct()
    {
        $itemsVisible = $this->cart->getQuote()->getAllVisibleItems();

        foreach($itemsVisible as $item){                

               $productId = $item->getProduct()->getId();
               //echo $productId;
               $item_s = $this->product->load($productId);
               
               if($item_s->getAgeVerify() == 1){                    

               $itemId = $item->getItemId();
               
               $this->cart->removeItem($itemId)->save();
            }

        }
    }

    public function getCatalogSession() 
    {
        return $this->_catalogSession;
    }
}