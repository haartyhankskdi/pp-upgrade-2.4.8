<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Model\Indexer;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Profiler;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\CollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Indexer
 *
 * @package Mageplaza\PromoBanner\Model\Indexer
 */
class Indexer extends IndexBuilder
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductInterface
     */
    protected $products;

    /**
     * Indexer constructor.
     *
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreManagerInterface $storeManager
     * @param Config $eavConfig
     * @param \Magento\Framework\Stdlib\DateTime $dateFormat
     * @param ProductFactory $productFactory
     * @param ResourceConnection $resource
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param int $batchCount
     */

    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager,
        Config $eavConfig,
        \Magento\Framework\Stdlib\DateTime $dateFormat,
        ProductFactory $productFactory,
        ResourceConnection $resource,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        DateTime $dateTime,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        $batchCount = 1000
    ) {
        $this->connection            = $resource->getConnection();
        $this->collectionFactory     = $collectionFactory;
        $this->productRepository     = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        parent::__construct(
            $ruleCollectionFactory,
            $priceCurrency,
            $resource,
            $storeManager,
            $logger,
            $eavConfig,
            $dateFormat,
            $dateTime,
            $productFactory,
            $batchCount
        );
    }

    /**
     * Reindex by ids
     *
     * @param array $ids
     *
     * @throws LocalizedException
     */
    public function reindexByIds(array $ids)
    {
        try {
            $this->doReindexByIds($ids);
        } catch (Exception $e) {
            $this->critical($e);
            throw new LocalizedException(
                __('Mageplaza PromoBanner indexing failed. See details in exception log.')
            );
        }
    }

    /**
     * Reindex by ids. Template method
     *
     * @param array $ids
     *
     * @return void
     * @throws Exception
     */
    protected function doReindexByIds($ids)
    {
        $this->cleanByIds($ids);

        $products = $this->getProducts($ids);
        foreach ($this->getActiveTemplates() as $template) {
            foreach ($products as $product) {
                /** @var CatalogProduct $product */
                $this->applyMpRule($template, $product);
            }
        }
    }

    /**
     * Clean by product ids
     *
     * @param array $productIds
     *
     * @return void
     */
    protected function cleanByIds($productIds)
    {
        $query = $this->connection->deleteFromSelect(
            $this->connection
                ->select()
                ->from($this->resource->getTableName('mageplaza_promobanner_actions_index'), 'product_id')
                ->distinct()
                ->where('product_id IN (?)', $productIds),
            $this->resource->getTableName('mageplaza_promobanner_actions_index')
        );
        $this->connection->query($query);
    }

    /**
     * Get active templates
     *
     * @return Collection
     */
    protected function getActiveTemplates()
    {
        return $this->getAllTemplates()
            ->addActiveFilter(null, null, $this->dateTime->date())
            ->addFieldToFilter('page', 1)
            ->addFieldToFilter('show_product_page', 1);
    }

    /**
     * Get All templates
     *
     * @return Collection
     */
    protected function getAllTemplates()
    {
        return $this->collectionFactory->create();
    }

    /**
     * @param Banner $banner
     * @param Product $product
     *
     * @return                                  $this
     * @throws                                  Exception
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function applyMpRule(Banner $banner, $product)
    {
        $bannerId        = $banner->getId();
        $productEntityId = $product->getId();

        if (!$banner->getActions()->validate($product)) {
            return $this;
        }

        $this->connection->delete(
            $this->resource->getTableName('mageplaza_promobanner_actions_index'),
            [
                $this->connection->quoteInto('banner_id = ?', $bannerId),
                $this->connection->quoteInto('product_id = ?', $productEntityId)
            ]
        );

        $rows = [];
        try {
            $rows[] = [
                'banner_id'  => $bannerId,
                'product_id' => $productEntityId,
            ];

            if (count($rows) === 1000) {
                $this->connection->insertMultiple($this->getTable('mageplaza_promobanner_actions_index'), $rows);
                $rows = [];
            }

            if (!empty($rows)) {
                $table = $this->resource->getTableName('mageplaza_promobanner_actions_index');
                $this->connection->insertMultiple($table, $rows);
            }
        } catch (Exception $e) {
            $this->critical($e);
        }

        return $this;
    }

    /**
     * Full reindex
     *
     * @return void
     * @throws LocalizedException
     * @api
     */
    public function reindexFull()
    {
        try {
            $this->doReindexFull();
        } catch (Exception $e) {
            $this->critical($e);
            throw new LocalizedException(
                __('Mageplaza PromoBanner indexing failed. See details in exception log.')
            );
        }
    }

    /**
     * Full reindex Template method
     *
     * @return void
     */
    protected function doReindexFull()
    {
        $this->connection->truncateTable(
            $this->getTable('mageplaza_promobanner_actions_index')
        );

        foreach ($this->getActiveTemplates() as $template) {
            $this->execute($template, 1000);
        }
    }

    /**
     * Reindex information about template relations with products.
     *
     * @param Banner $banner
     * @param  $batchCount
     *
     * @return bool
     */
    protected function execute(Banner $banner, $batchCount)
    {
        if (!$banner->getStatus()) {
            return false;
        }

        $connection = $this->resource->getConnection();

        Profiler::start('__MATCH_PRODUCTS__');
        $productIds = $banner->getMatchingProductIds();
        Profiler::stop('__MATCH_PRODUCTS__');

        $indexTable = $this->resource->getTableName('mageplaza_promobanner_actions_index');

        $bannerId = $banner->getId();
        $rows     = [];

        foreach ($productIds as $productId) {
            $rows[] = [
                'banner_id'  => $bannerId,
                'product_id' => $productId
            ];

            if (count($rows) === $batchCount) {
                $connection->insertMultiple($indexTable, $rows);
                $rows = [];
            }
        }
        if (!empty($rows)) {
            $connection->insertMultiple($indexTable, $rows);
        }

        return true;
    }

    /**
     * Get products by ids
     *
     * @param array $productIds
     *
     * @return ProductInterface[]
     */
    public function getProducts($productIds)
    {
        if ($this->products === null) {
            $this->searchCriteriaBuilder->addFilter('entity_id', $productIds, 'in');
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $this->products = $this->productRepository->getList($searchCriteria)->getItems();
        }

        return $this->products;
    }
}
