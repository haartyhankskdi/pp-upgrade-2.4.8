<?php

namespace Haartyhanks\CategoryPost\Block;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Layer\Resolver;

class CurrentCategory extends Template
{
    protected $layerResolver;

    public function __construct(
        Template\Context $context,
        Resolver $layerResolver,
        array $data = []
    ) {
        $this->layerResolver = $layerResolver;
        parent::__construct($context, $data);
    }

    /**
     * Get current category ID from layer resolver
     *
     * @return int|null
     */
    public function getCurrentCategoryId()
    {
        // Get the current category from the layer resolver
        $category = $this->layerResolver->get()->getCurrentCategory();
        if ($category && $category->getId()) {
            return $category->getId();
        }
        return null;
    }

    /**
     * Get current category name from layer resolver
     *
     * @return string|null
     */
    public function getCurrentCategoryName()
    {
        // Get the current category from the layer resolver
        $category = $this->layerResolver->get()->getCurrentCategory();
        if ($category && $category->getName()) {
            return $category->getName();
        }
        return null;
    }
}