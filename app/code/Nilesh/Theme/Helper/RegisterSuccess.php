<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Theme\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Request\Http;

class RegisterSuccess extends AbstractHelper
{
    protected $objectManager;
    protected $request;
    protected $_customerSession;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        Http $request
    ) {
        parent::__construct($context);
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->request = $request;
        $this->_customerSession = $customerSession;
    }

    public function setCartRedirect()
    {
        $redirect = $this->objectManager->get('\Magento\Framework\App\Response\Http');
        $storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        // $params = array('referer' => base64_encode($storeManager->getStore()->getBaseUrl().'onestepcheckout'));
        $param = base64_encode($storeManager->getStore()->getBaseUrl().'onestepcheckout');
        return $redirect->setRedirect("customer/general/question/referer/$param");
        // return $redirect;
        // $redirect->setRedirect('');
    }

    public function setCheckOutRedirect()
    {
        $redirect = $this->objectManager->get('\Magento\Framework\App\Response\Http');
        return $redirect->setRedirect("onestepcheckout");
    }

    /* public function setMyAccountRedirect()
    {
        $redirect = $this->objectManager->get('\Magento\Framework\App\Response\Http');
        return $redirect->setRedirect("customer/account");
    } */

    public function cartIsNotEmpty()
    {
        $cart = $this->objectManager->get(\Magento\Checkout\Model\Cart::class);
        $items = $cart->getQuote()->getAllItems();
        $totalItems= count($items);
        if($totalItems > 0){
            return true;
        }
        return false;
    }

    public function getRefBack()
    {
        return $this->request->getParam('refback');
    }

    /* This For registerr page */
    public function setSessionRefBack()
    {
        return $this->_customerSession->setRefback('yes');
    }
    
    public function getSessionRefBack()
    {
        return $this->_customerSession->getRefback();
    }
    
    public function unSetSessionRefBack()
    {
        return $this->_customerSession->unsRefback();
    }
}
