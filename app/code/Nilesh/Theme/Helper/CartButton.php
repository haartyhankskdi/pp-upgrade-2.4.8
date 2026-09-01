<?php

namespace Nilesh\Theme\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class CartButton extends AbstractHelper
{

    public function __construct(
        Context $context
    )
    {
        parent::__construct($context);
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function getCartNeedGeneralQuestion()
    {

        $cart = $this->objectManager->get(\Magento\Checkout\Model\Cart::class);
        $items = $cart->getQuote()->getAllItems();
        $_product = $this->objectManager->get(\Magento\Catalog\Model\ProductRepository::class);
        // if(isset($items)){
            foreach($items as $item) {
                $productId = $item->getProductId();
                $product = $_product->getById($productId);
                if($product->getData('general_question') == 1){
                    return true;
                }
            }
        // }
        return false;
    }

    public function isLogin()
    {
        $customerSession = $this->objectManager->get('Magento\Customer\Model\Session');
        if($customerSession->isLoggedIn()) {
           return true;
        }
    }

    public function getNeedOfGQ()
    {
        $customer = $this->objectManager->get(\Magento\Customer\Model\Session::class);
        return $customer->getCustomer()->getData('general_question');
    }

}