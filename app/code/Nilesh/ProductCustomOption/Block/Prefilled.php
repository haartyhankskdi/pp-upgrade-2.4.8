<?php declare(strict_types=1);
/**
 * This module is created by Nilesh Dubey
 * Copyright (C) 2020  Free
 * 
 * This file is part of Nilesh/ProductCustomOption.
 * 
 * Nilesh/ProductCustomOption is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Nilesh\ProductCustomOption\Block;


class Prefilled extends \Magento\Framework\View\Element\Template
{
    /**
     * Property 
     * @var $_objectManager
    */
    protected $_objectManager;
    protected $_registry;
    protected $_catOption;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product\Option $catOption,
        array $data = []
    ) {
        parent::__construct($context, $data);
        // $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_registry = $registry;
        $this->_catOption = $catOption;
    }

    /**
     * @return Array
     */
    public function haveCustomOption()
    {
        $product = $this->getCurrentProduct();
        $customOptions = $this->_catOption->getProductOptionCollection($product);
        if(count($customOptions) > 0){
           return array(
               'product_id' => $product->getId(),
               'status' => true
           );
          }
        return array(
            'product_id' => $product->getId(),
            'status' => false
        );;
    }

    /**
     * @return Object
     */
    private function getCurrentProduct()
    {
        // $_registry = $this->_registry->create();
        return $this->_registry->registry('current_product');
    }

    // public function getParentCustomQuestionArray()
    // {
        //Your block code
        // return __('Hello Developer! This how to get the storename: %1 and this is the way to build a url: %2', $this->_storeManager->getStore()->getName(), $this->getUrl('contacts'));
    // }
}

