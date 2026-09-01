<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Block\Ageverification;

class Index extends \Magento\Framework\View\Element\Template
{
    protected $customerSession;
    protected $_addressFactory;
    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->_addressFactory = $addressFactory;
        parent::__construct($context, $data);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function getDatanew()
    {

        $customerData = $this->customerSession->getCustomer()->getData(); //get all data of customerData
        //$customerData = $this->_customerSession->getCustomer()->getId();//get id of customer
        return $customerData;
    }

    public function getBillingAddress()
    {
    //$customerData = $this->customerSession->getDefaultBilling();
    	//billing
    $billingAddressId = $this->customerSession->getCustomer()->getDefaultBilling();
    $billingAddress = $this->_addressFactory->create()->load($billingAddressId);
    return $billingAddress->getData();
    }
}

