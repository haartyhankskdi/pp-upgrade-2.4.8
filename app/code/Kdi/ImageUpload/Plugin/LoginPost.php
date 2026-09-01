<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Plugin;

use Magento\Framework\Controller\ResultFactory;
use Kdi\ImageUpload\Helper\CustomCookie;
use Magento\Store\Model\StoreManagerInterface;

class LoginPost
{
    protected $resultFactory;
    protected $customCookie;
    protected $storeManager;


    public function __construct( 
        ResultFactory $redirect,
        CustomCookie $customCookie,
        StoreManagerInterface $storeManager
    ) { 
        $this->resultFactory = $redirect;
        $this->customCookie = $customCookie;
        $this->storeManager = $storeManager;

    }

    public function afterExecute(
        \Magento\Customer\Controller\Account\LoginPost $subject,
        $result
    ) {

        $currentStore = $this->storeManager->getStore();
        $currentStoreCode = $currentStore->getCode();
        $defaultStoreCode = $this->storeManager->getDefaultStoreView()->getCode();

       if ($currentStoreCode === $defaultStoreCode) {
             $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT); 
        $data = $this->customCookie->get();

        if ($data) {
            $resultRedirect->setUrl('/image_upload?order_id='.$data);
            return $resultRedirect;
        }
        else {
            $resultRedirect->setUrl('/image_upload/customer/account');
            return $resultRedirect;
        }
        }
        return $result;
       
    }
}
