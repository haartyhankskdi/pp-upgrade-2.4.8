<?php

namespace Kdi\ImageUpload\Plugin;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class CategorySortByPosition
{
    public function aroundGetProductCollection(Category $subject, \Closure $proceed)
    {
        $collection = $proceed();
        if ($collection instanceof Collection) {
            $collection->setOrder('product_position', 'ASC'); // Sorting by position in ascending order
        }
        return $collection;
    }
}
