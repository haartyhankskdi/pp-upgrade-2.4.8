<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel\Post;

use Magefan\Blog\Model\ResourceModel\Post\Collection;

class CollectionPlugin
{
    /**
     * @var \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter
     */
    protected $customerGroupFilter;

    /**
     * CollectionPlugin constructor.
     * @param \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
     */
    public function __construct(
        \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
    ) {
        $this->customerGroupFilter = $customerGroupFilter;
    }

    /**
     * Applies group filter to the collection after adding the active filter.
     *
     * @param Collection $subject
     * @param mixed $result
     */
    public function afterAddActiveFilter(Collection $subject, $result)
    {
        $this->customerGroupFilter->addGroupFilter($subject);
        return $result;
    }
}
