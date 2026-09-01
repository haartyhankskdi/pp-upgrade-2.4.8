<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Controller\Customer;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Kdi\ImageUpload\Helper\CustomCookie;
class Account implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $customerSession;
    protected $customCookie;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(PageFactory $resultPageFactory, 
    Session $customerSession,
    ResultFactory $resultFactory,
    CustomCookie $customCookie
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->resultFactory = $resultFactory;
        $this->customCookie = $customCookie;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {


        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
             $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                    $redirect->setUrl('/customer/account/login');
                    return $redirect;
        }
        return $this->resultPageFactory->create();
    }
}

