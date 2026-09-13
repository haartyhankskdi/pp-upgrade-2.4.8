<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel;

use Magefan\Blog\Model\ResourceModel\Category;

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
     * Add customer group filter to collection
     *
     * @param Category $resourceModel
     * @param mixed $result
     * @param mixed $subject
     * @return mixed
     */
    public function afterLoad(Category $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->loadGroupFilter($subject);

        return $result;
    }

    /**
     * Save customer group filter to collection
     *
     * @param Category $resourceModel
     * @param mixed $result
     * @param mixed $subject
     * @return mixed
     */
    public function afterSave(Category $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->saveGroupFilter($subject);

        return $result;
    }

    /**
     * Delete customer group filter from collection
     *
     * @param Category $resourceModel
     * @param mixed $result
     * @param mixed $subject
     * @return mixed
     */
    public function afterDelete(Category $resourceModel, $result, $subject)
    {
        $this->customerGroupFilter->deleteGroupFilter($subject);

        return $result;
    }
}
