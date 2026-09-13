<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model\AutoRelated;

class ProductProcessor extends AbstractProcessor
{
    /**
     * @var string
     */
    protected $autoRelatedTable = 'magefan_blog_post_relatedproduct';

    /**
     * @var string
     */
    protected $autoRelatedLogTable = 'magefan_blog_post_relatedproduct_log';

    /**
     * @var \Magento\Catalog\Model\Layer\SearchFactory
     */
    private $searchFactory;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Catalog\Model\Layer\SearchFactory $searchFactory
     * @param \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory
     * @param \Magefan\Blog\Model\ResourceModel\Post $resource
     * @param \Magefan\BlogPlus\Model\Config $config
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\SearchFactory $searchFactory,
        \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory,
        \Magefan\Blog\Model\ResourceModel\Post $resource,
        \Magefan\BlogPlus\Model\Config $config,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($postCollectionFactory, $resource, $config);
        $this->searchFactory = $searchFactory;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
    }

    /**
     * Get related product ids from post
     *
     * @param \Magefan\Blog\Model\Post $post
     * @return array
     */
    protected function getRelatedIds($post)
    {
        return $post->getRelatedProducts()->getAllIds();
    }

    /**
     * Get auto related product ids from post
     *
     * @param \Magefan\Blog\Model\Post $post
     * @return mixed[]
     */
    public function getAutoRelatedIds(
        \Magefan\Blog\Model\Post $post
    ): array {

        $ids = [];
        $storeIds = $post->getStoreIds();
        if (!$storeIds) {
            return $ids;
        }

        if (in_array(0, $storeIds)) {
            $storeIds = [];
            foreach ($this->storeManager->getStores(false) as $store) {
                if ($store->isActive()) {
                    $storeIds[] = (int)$store->getId();
                }
            }
        }

        foreach ($storeIds as $storeId) {

            if (!$this->storeManager->getStore($storeId)->getRootCategoryId()) {
                continue;
            }

            $title = $this->extractKeywords($post->getTitle());

            $this->appEmulation->startEnvironmentEmulation($storeId);

            $productCollection = $this->searchFactory->create()->getProductCollection();
            $productCollection->setStoreId($storeId);
            $productCollection->addSearchFilter($title);
            $productCollection->setOrder('relevance', 'desc');
            $productCollection->setPageSize($this->config->getRelatedProductsCount() * 2);

            foreach ($productCollection as $item) {
                $ids[] = $item->getId();
            }

            $this->appEmulation->stopEnvironmentEmulation();
        }

        $ids = array_unique($ids);

        return $ids;
    }

    /**
     * Retrieve tru if can generate auto related items
     *
     * @return bool
     */
    protected function autoRelatedEnabled(): bool
    {
        return $this->config->isAutoRelatedProductsEnabled();
    }

    /**
     * Get array of black words from Abstract Processor and from admin panel and join two arrays
     *
     * @return array
     */
    protected function getIgnoredWords(): array
    {
        return array_merge(
            parent::getIgnoredWords(),
            $this->config->getAutoRelatedProductsBlackWords()
        );
    }
}
