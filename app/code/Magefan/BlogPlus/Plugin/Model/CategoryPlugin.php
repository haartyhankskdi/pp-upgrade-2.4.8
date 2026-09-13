<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Model;

use Magefan\Blog\Model\Category;

class CategoryPlugin
{
    /**
     * @var \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter
     */
    protected $customerGroupFilter;

    /**
     * CategoryPlugin constructor.
     * @param \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
     */
    public function __construct(
        \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
    ) {
        $this->customerGroupFilter = $customerGroupFilter;
    }

    /**
     * Modify the result of the isVisibleOnStore method.
     *
     * @param Category $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsVisibleOnStore(Category $subject, $result)
    {
        if (!$result) {
            return $result;
        }

        return $this->customerGroupFilter->isVisibleForGroup($subject);
    }
}
