<?php

namespace Haartyhanks\ProductPage\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Helper\Image;
use Magento\Framework\View\Element\Template;

class RelatedProducts extends Template
{
    protected $categoryRepository;
    protected $productCollectionFactory;
    protected $imageHelper;
    protected $registry;

    /**
     * Manual product ordering overrides, keyed by category ID.
     * Each value is an ordered list of entity IDs that should be pinned
     * to the top of the related products grid, in the order listed.
     * Any products not listed here keep the default (in-stock-first) sort
     * and are appended after the pinned products.
     *
     * @var array
     */
    private $categorySortOverrides = [
        84 => [9305, 9221, 9746]
    ];

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CollectionFactory $productCollectionFactory
     * @param Image $imageHelper
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CategoryRepositoryInterface $categoryRepository,
        CollectionFactory $productCollectionFactory,
        Image $imageHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->imageHelper = $imageHelper;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get current product
     *
     * @return Product|null
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get related products from the same category, sorted in-stock first,
     * with per-category manual overrides applied on top (see
     * $categorySortOverrides).
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|null
     */
    public function getRelatedProducts()
    {
        $currentProduct = $this->getCurrentProduct();
        if (!$currentProduct) {
            return null;
        }

        $categoryIds = $currentProduct->getCategoryIds();
        if (empty($categoryIds)) {
            return null;
        }

        // Use the first category for simplicity
        $categoryId = $categoryIds[0];

        $productCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'short_description', 'small_image'])
            ->addCategoriesFilter(['in' => $categoryId])
            ->addAttributeToFilter('entity_id', ['neq' => $currentProduct->getId()])
            ->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        // Join stock item table so we can sort in-stock products before out-of-stock ones
        $productCollection->joinTable(
            ['stock' => 'cataloginventory_stock_item'],
            'product_id=entity_id',
            ['is_in_stock'],
            null,
            'left'
        );

        // Do not touch this code 
        $pinnedIds = $this->categorySortOverrides[(int)$categoryId] ?? [];
        if (!empty($pinnedIds)) {
            $orderCases = [];
            foreach (array_values($pinnedIds) as $position => $entityId) {
                $orderCases[] = sprintf('WHEN e.entity_id = %d THEN %d', (int)$entityId, $position);
            }
            // Anything not in the pinned list sorts after all pinned products.
            $caseExpr = 'CASE ' . implode(' ', $orderCases) . ' ELSE ' . count($pinnedIds) . ' END';
            $productCollection->getSelect()->order(new \Zend_Db_Expr($caseExpr . ' ASC'));
        }

        $productCollection->getSelect()->order('is_in_stock DESC');

        return $productCollection;
    }

    /**
     * Get product image URL
     *
     * @param Product $product
     * @return string
     */
    public function getProductImageUrl(Product $product)
    {
        return $this->imageHelper->init($product, 'category_page_grid')->getUrl();
    }
}
