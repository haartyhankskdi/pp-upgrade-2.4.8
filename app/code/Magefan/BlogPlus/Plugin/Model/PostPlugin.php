<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Model;

use Magefan\Blog\Model\Post;

class PostPlugin
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter
     */
    protected $customerGroupFilter;

    /**
     * PostPlugin constructor.
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magefan\BlogPlus\Model\ResourceModel\CustomerGroupFilter $customerGroupFilter
    ) {
        $this->customerGroupFilter = $customerGroupFilter;
        $this->request = $request;
    }

    /**
     * Add additional columns to related products collection
     *
     * @param Post $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $result
     * @return mixed
     */
    public function afterGetRelatedProducts(Post $subject, $result)
    {
        $result->getSelect()->columns([
            'display_on_product' => 'rl.display_on_product',
            'display_on_post' => 'rl.display_on_post',
            'auto_related' => 'rl.auto_related'
        ]);

        return $result;
    }

    /**
     * Add additional columns to related posts collection
     *
     * @param Post $subject
     * @param \Magefan\Blog\Model\ResourceModel\Post\Collection $result
     * @return mixed
     */
    public function afterGetRelatedPosts(Post $subject, $result)
    {
        $result->getSelect()->columns([
            'auto_related' => 'rl.auto_related'
        ]);

        return $result;
    }

    /**
     * Is visible on store filter
     *
     * @param Post $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsVisibleOnStore(Post $subject, $result)
    {
        if (!$result) {
            return $result;
        }

        return $this->customerGroupFilter->isVisibleForGroup($subject);
    }
}
