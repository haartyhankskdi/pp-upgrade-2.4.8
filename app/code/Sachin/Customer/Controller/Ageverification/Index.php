<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Controller\Ageverification;
use \Magento\Customer\Model\Session as CustomerSession;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    public $customerSession;
    public $soapClientFactory;

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
        // $wsdl = "https://sandbox.ws-idu.tracesmart.co.uk/v5.6/?wsdl";
        // $soapClient = $this->soapClientFactory->create($wsdl,array('login' => "20018141",'password' => "vBTGZni9KDNLM3SuXeXCnDAGZPuz"));


        return $this->resultPageFactory->create();
    }
}

