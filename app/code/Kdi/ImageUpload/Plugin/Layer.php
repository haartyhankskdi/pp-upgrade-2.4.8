<?php
namespace Kdi\ImageUpload\Plugin;

/**
 * Class Layer
 * @package My\Namespace\Plugin\Catalog\Model\Layer
 */
class Layer
{
  /**
  * Sort items that are not salable last
  *
  * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
  */
  public function afterGetProductCollection(
      \Magento\Catalog\Model\Layer $subject,
      \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
  ) {
  $fromPart = $collection->getSelect()->getPart(\Zend_Db_Select::FROM);

        if (!array_key_exists('stock_status', $fromPart)) {
            // Join stock status table only if it's not already joined
            $collection->getSelect()->joinLeft(
                ['stock_status' => $collection->getTable('cataloginventory_stock_status')],
                'e.entity_id = stock_status.product_id AND stock_status.website_id = 1', // Adjust website_id as needed
                ['stock_status' => 'stock_status']
            );
        }

        // Sort products by stock status (in-stock first)
        $collection->getSelect()->order('stock_status.stock_status DESC');

        return $collection;
  }
}