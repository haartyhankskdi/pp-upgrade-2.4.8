<?php

namespace Haartyhanks\CategoryQuest\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as Customer;

class BlacklistedPopup extends Template
{
    protected $customerFactory;
    protected $customer;

    public function __construct
    (
        CustomerFactory $customerFactory,
        Customer $customer,
        Context $context,
        array $data = []
    )
    {
        $this->customerFactory = $customerFactory;
        $this->customer = $customer;
        parent::__construct($context, $data);
    }

    public function getCustomerId()
    {
        $customer = $this->customer;
        return $customer->getId();
    }

    public function getCustomerData()
    {
        $customerId = $this->getCustomerId();
        $customerFactory = $this->customerFactory->create();
        $customer = $customerFactory->load($customerId);
        return $customer;
    }
}