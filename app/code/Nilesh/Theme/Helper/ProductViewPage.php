<?php

namespace Nilesh\Theme\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class ProductViewPage extends AbstractHelper
{
    public function __construct(
        Context $context
    )
    {
        parent::__construct($context);
    }

    public function getCatLink()
    {
        $catUrl = array();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $category = $objectManager->get('Magento\Framework\Registry')->registry('current_category');//get current category
        // print_r($category); exit(1);
        if(!empty($category)){
        $parCatId = $category->getId(); // current Category ID
        $parCategory = $objectManager->create('Magento\Catalog\Model\Category')->load($parCatId);
        $parent_category = $parCategory->getParentCategory();
        // $catName = $parent_category->getName();

        /* TODO: This is static need to change if root category change */
        if(!empty($parent_category) && $parent_category->getId() == '79'){
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $catUrl[] = array('url' => $storeManager->getStore()->getBaseUrl().'start-consultation.html', 'name' => $parent_category->getName());
        }else{
            $catUrl[] = array('url' => $parent_category->getUrl(), 'name' => $parent_category->getName());
        }

        $catUrl[] = array('url' => $parCategory->getUrl(), 'name' => $parCategory->getName());
        }
        // print_r($parCategory->getData()); exit;
        return $catUrl;
    }
}