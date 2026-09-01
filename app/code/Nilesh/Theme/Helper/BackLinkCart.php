<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Theme\Helper;
use Magento\Framework\App\Helper\AbstractHelper;

class BackLinkCart extends AbstractHelper
{
    protected $_checkoutSession;
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function setBackProductId($product_id) 
    {
        return $this->_checkoutSession->setBackProductId($product_id);
    }

    public function getBackProductId()
    {
        return $this->_checkoutSession->getBackProductId();
    }

    public function unSetBackProductId()
    {
        return $this->_checkoutSession->unsBackProductId();
    }

    public function lastCartItem() 
    {
        $items = $this->_checkoutSession->getQuote()->getAllVisibleItems();
        $max = 0;
        $lastItem = array();
        foreach ($items as $item){
            if ($item->getId() > $max) {
                $max = $item->getId();
                $lastItem['item_id'] = $item->getId();
                $lastItem['product_id'] = $item->getProductId();
            }
        }

        return $lastItem;
    }

    
}
