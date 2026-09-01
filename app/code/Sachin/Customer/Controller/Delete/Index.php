<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Controller\Delete;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $_customerSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->_customerSession->isLoggedIn()) {
            $this->_redirect('/customer/account/login/');
        }
        return $this->resultPageFactory->create();
    }
}

