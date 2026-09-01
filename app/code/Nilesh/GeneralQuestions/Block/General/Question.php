<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Block\General;

use Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions\Collection as GeneralQuestions;
use \Magento\Customer\Model\Session as CustomerSession;

class Question extends \Magento\Framework\View\Element\Template
{

    /**
     * @var $generalQuestions
     * @var $customerSession
     */
    protected $customerSession;
    protected $generalQuestions;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    
    public function __construct(
        CustomerSession $customerSession,
        GeneralQuestions $generalQuestions,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->generalQuestions = $generalQuestions;
        parent::__construct($context, $data);
    }

    public function getReferalUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $objectManager->get(\Magento\Framework\App\Request\Http::class);
        return $request->getParam('referer');
    }
    
    public function comingFromRegister()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $objectManager->get(\Magento\Framework\App\Request\Http::class);
        return $request->getParam('reg');
    }

    public function getCustomerData()
    {
        /**
         * Step to do so
         * 1. Need customer id
         * 2. Need to get custom question
         */
        $customerId = $this->customerSession->getCustomer()->getId();

        $model = $this->generalQuestions->addFieldToFilter("customer_id", $customerId)->setPageSize(1)->setOrder('generalquestions_id', 'DESC')->load();
        return $model->getData();
    }
}

