<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Controller\Adminhtml\Ageverify;

class Test extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $helperData;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Sachin\Customer\Helper\Registerverify $helperData
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $data = $this->getRequest()->getPost();
        $abc = $this->helperData->AdminVerifyAge($data['customer_id'],$data['firstname'],$data['lastname'],$data['dob'],$data['gender'],$data['street'],$data['city'],$data['postcode']);
        print_r($abc);exit();
        $this->messageManager->addSuccess(__("Reminder set successfully"));
        //return $this->resultPageFactory->create();
    }
}