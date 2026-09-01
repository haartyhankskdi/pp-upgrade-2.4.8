<?php

namespace Haartyhanks\LumaChild\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;

class WeightlossComparison extends Template
{
    protected $productRepository;
    protected $registry;

    public function __construct(
        Template\Context $context,
        ProductRepositoryInterface $productRepository,
        Registry $registry,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    public function getProductId()
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getId() : null;
    }

    public function isAllowedProduct()
    {
        $allowedProductIds = [9305, 9221, 9338, 9567, 2703, 9395, 9232, 8294, 9405, 9337];
        $productId = $this->getProductId();
        return $productId && in_array($productId, $allowedProductIds);
    }

    public function getProductAttribute($attributeCode)
    {
        $product = $this->getCurrentProduct();
        return $product ? $product->getData($attributeCode) : null;
    }
}
