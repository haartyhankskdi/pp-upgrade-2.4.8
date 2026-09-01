<?php

namespace Haartyhanks\ProductPage\Block;

use Magento\Framework\Registry;

class ProductSection extends \Magento\Framework\View\Element\Template
{
    protected $_registry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    public function getCurrentProduct()
    {
        $product = $this->_registry->registry('current_product');

        // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/Helper_data.log');
        // $zendLogger = new \Zend_Log();
        // $zendLogger->addWriter($writer);

        if ($product) {
            $categoryIds = $product->getCategoryIds();

            // Check if 84 exists in categoryIds
            $hasCategory84 = in_array(84, $categoryIds);
            return $hasCategory84;
        }

        return false;
    }

}