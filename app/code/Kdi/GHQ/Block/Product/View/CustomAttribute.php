<?php
namespace Kdi\GHQ\Block\Product\View;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Block\Product\Context;

class CustomAttribute extends Template
{
    protected $product;

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->product = $context->getRegistry()->registry('current_product');
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getCustomAttributeValue($attributeCode)
    {
        if (!$this->product) {
            return null;
        }

        return $this->product->getData($attributeCode);
    }
}
