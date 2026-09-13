<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model;

use Magefan\Community\Api\GetWebsitesMapInterface;
use Magefan\Community\Model\Magento\Product\CollectionOptimizedForSqlValidatorFactory;
use Magefan\Community\Model\Magento\Rule\Model\Condition\Sql\Builder;

class RelatedProductsRule
{

    public const POSITION = 100;

    /**
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $datetime;

    /**
     * @var CollectionOptimizedForSqlValidatorFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array|null
     */
    protected $productIds;

    /**
     * @var Builder
     */
    private $sqlBuilder;

    /**
     * @var GetWebsitesMapInterface
     */
    private $getWebsitesMap;

    /**
     * RelatedProductsRule constructor.
     *
     * @param \Magento\CatalogRule\Model\RuleFactory $ruleFactory
     * @param CollectionOptimizedForSqlValidatorFactory $productCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Builder $sqlBuilder
     * @param GetWebsitesMapInterface $getWebsitesMap
     */
    public function __construct(
        \Magento\CatalogRule\Model\RuleFactory    $ruleFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        CollectionOptimizedForSqlValidatorFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Builder                                   $sqlBuilder,
        GetWebsitesMapInterface                   $getWebsitesMap
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->datetime = $datetime;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->sqlBuilder = $sqlBuilder;
        $this->getWebsitesMap = $getWebsitesMap;
    }

    /**
     * Update related products by condition rule
     *
     * @param \Magefan\Blog\Model\Post $post
     * @return void
     */
    public function updateRelatedProducts(\Magefan\Blog\Model\Post $post): void
    {
        $postId = (int)$post->getId();
        if (!$postId) {
            return;
        }

        $cs = $post->getData('rp_conditions_serialized');
        if (!$cs) {
            return;
        }

        $rule = $this->buildRule($cs);
        if (!$rule) {
            return;
        }

        $storeIds = [0];

        $resource = $post->getResource();
        $connection = $resource->getConnection();
        $table = $resource->getTable('magefan_blog_post_relatedproduct_by_rule');

        $connection->delete($table, ['post_id = ?' => $postId]);

        foreach ($storeIds as $storeId) {
            $storeId = (int)$storeId;
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->setStoreId($storeId);

            if (!$this->attachConditionsToCollection($rule, $productCollection)) {
                continue;
            }

            $select = $productCollection->getSelect();
            $select->reset(\Magento\Framework\DB\Select::COLUMNS);
            $select->columns([
                'post_id'    => new \Zend_Db_Expr((string)$postId),
                'product_id' => 'e.entity_id',
                'store_id'   => new \Zend_Db_Expr((string)$storeId),
            ]);
            $select->group('e.entity_id');
            $select->limit(10000);

            $connection->query(
                $connection->insertFromSelect($select, $table, ['post_id', 'product_id', 'store_id'])
            );
        }

        $generationDate = date(
            'Y-m-d H:i:s',
            $this->datetime->gmtTimestamp()
        );

        $connection->update(
            $resource->getTable('magefan_blog_post'),
            ['rp_conditions_generation_time' => $generationDate],
            ['post_id = ?' => $postId]
        );
    }

    /**
     * Get list of product ids by condition rule
     *
     * @param mixed $rule
     * @return array
     */
    protected function getListProductIds($rule): array
    {
        $this->productIds = [];

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setStoreId($this->storeManager->getStore()->getId());

        if ($this->attachConditionsToCollection($rule, $productCollection)) {
            $productCollection->getSelect()->group('e.entity_id')->limit(10000);

            foreach ($productCollection as $item) {
                $this->productIds[] = (int)$item->getId();
            }
        }

        return $this->productIds;
    }

    /**
     * Build catalog rule from serialized conditions string.
     *
     * @param string $cs
     * @return \Magento\CatalogRule\Model\Rule|null
     */
    private function buildRule(string $cs): ?\Magento\CatalogRule\Model\Rule
    {
        $array = json_decode($cs, true);
        if ($array) {
            $hasConditions = isset($array['conditions']);
        } else {
            $hasConditions = (strpos($cs, '"conditions"') !== false); //fix for M2.1.x
        }

        if (!$hasConditions) {
            return null;
        }

        $rule = $this->ruleFactory->create();
        $rule->setData('conditions_serialized', $cs);
        $rule->loadPost($rule->getData());

        return $rule;
    }

    /**
     * Attach rule conditions to product collection.
     *
     * @param \Magento\CatalogRule\Model\Rule $rule
     * @param mixed $productCollection
     * @return bool
     */
    private function attachConditionsToCollection(\Magento\CatalogRule\Model\Rule $rule, $productCollection): bool
    {
        $conditions = $rule->getConditions();

        if (empty($conditions['conditions'])) {
            return false;
        }

        $conditions = $rule->getConditions()->asArray();
        $rule->getConditions()->setConditions([])->loadArray($conditions);
        $conditions = $rule->getConditions();
        $conditions->collectValidatedAttributes($productCollection);
        $this->sqlBuilder->attachConditionToCollection($productCollection, $conditions);

        return true;
    }
}
