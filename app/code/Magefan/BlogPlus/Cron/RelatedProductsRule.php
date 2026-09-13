<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Cron;

class RelatedProductsRule
{

    /**
     *  Post's collection limit
     */
    public const POST_COUNT = 100;

    /**
     * @var \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory
     */
    protected $postCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $datetime;

    /**
     * @var \Magefan\BlogPlus\Model\RelatedProductsRuleFactory
     */
    protected $relatedProductsRuleFactory;

    /**
     * RelatedProductRule constructor.
     * @param \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magefan\BlogPlus\Model\RelatedProductsRuleFactory $relatedProductsRuleFactory
     */
    public function __construct(
        \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magefan\BlogPlus\Model\RelatedProductsRuleFactory $relatedProductsRuleFactory
    ) {
        $this->postCollectionFactory = $postCollectionFactory;
        $this->datetime = $datetime;
        $this->relatedProductsRuleFactory = $relatedProductsRuleFactory;
    }

    /**
     * Executes the process of updating related products for posts.
     *
     * @return void
     */
    public function execute(): void
    {
        // current date - 1 day
        $date = date(
            'Y-m-d H:i:s',
            $this->datetime->gmtTimestamp() - 86400
        );
        $posts = $this->postCollectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter('rp_conditions_serialized', ['notnull' => true])
            ->addFieldToFilter('rp_conditions_generation_time', ['lt' => $date ])
            ->setPageSize(self::POST_COUNT);

        foreach ($posts as $post) {
            $this->relatedProductsRuleFactory->create()->updateRelatedProducts($post);
        }
    }
}
