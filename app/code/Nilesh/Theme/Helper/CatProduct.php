<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Theme\Helper;
use Magento\Framework\App\Helper\AbstractHelper;

class CatProduct extends AbstractHelper
{
    const PAGESIZE = 2;
    protected $_objectManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        parent::__construct($context);
    }

    public function getChildrenCategories()
    {
        $category = $this->_objectManager->get('Magento\Framework\Registry')->registry('current_category');
        return $category->getChildrenCategories();
    }
    
    public function getRootChildrenCategories()
    {
        $category = $this->_objectManager->create('Magento\Catalog\Model\Category')->load('79');
        return $category->getChildrenCategories();
    }

    public function getSubCategory($subcat)
    {
        return $this->_objectManager->create('Magento\Catalog\Model\Category')->load($subcat);
    }

    public function getProductCollectionByCategories($categoryId)
    {
        $prod = array();
        $categoryFactory = $this->_objectManager->get('\Magento\Catalog\Model\CategoryFactory');
        $category = $categoryFactory->create()->load($categoryId);
        $categoryProducts = $category->getProductCollection();
        $categoryProducts->addAttributeToSelect('*');
        $categoryProducts->setPageSize(self::PAGESIZE);
        foreach ($categoryProducts as $product) {
            // get Product data
            // print_r($product->getData()); exit;
            $prod[] = array("name" => $product->getName(), "url" => $product->getProductUrl());
        }
        return $prod;
    }
}
