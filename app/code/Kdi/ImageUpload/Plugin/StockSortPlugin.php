<?php

namespace Kdi\ImageUpload\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class StockSortPlugin
{
   /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    public function __construct(StockRegistryInterface $stockRegistry)
    {
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Modify collection to sort by stock status
     *
     * @param ProductCollection $subject
     * @param ProductCollection $result
     * @return ProductCollection
     */
    public function afterLoad(ProductCollection $subject, ProductCollection $result)
    {
        $productIds = $result->getAllIds();
        $stockData = [];

        foreach ($productIds as $productId) {
            $stockItem = $this->stockRegistry->getStockItem($productId);
            $stockData[$productId] = $stockItem->getIsInStock();
        }

        usort($result->getItems(), function ($a, $b) use ($stockData) {
            return $stockData[$b->getId()] <=> $stockData[$a->getId()];
        });

        return $result;
    }
}
