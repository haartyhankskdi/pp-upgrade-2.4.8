<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Controller\General;
use \Magento\Customer\Model\Session as CustomerSession;

class Question extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $customerSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        CustomerSession $customerSession,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if(!$this->customerSession->isLoggedIn()){
            $this->_redirect('customer/account/login');
            return;
        }
        return $this->resultPageFactory->create();
    }
}

