<?php

namespace Nilesh\Theme\Helper;

use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class ConfigurableProduct extends AbstractHelper
{
    public function __construct(Context $context, ProductFactory $productFactory, Item $stockItem)
    {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->stockItem      = $stockItem;
    }
    
    public function getChildProducts($_productId)
    {
        $outOfStockProducts = array();
        try {
            $configProduct = $this->productFactory->create()->load($_productId);
            $childProducts = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
            foreach ($childProducts as $childProduct) {
                $stockItem = $this->getStockItem($childProduct->getID());
                if (!$stockItem->getQty()) {
                    $outOfStockProducts[]  = $childProduct;
                }
            }
        }
        catch (\Exception $e) {
            return $e->getMassage();
        }
        return $outOfStockProducts;
    }

    public function getStockItem($productId)
    {
        $stockItem = $this->stockItem->load($productId, 'product_id');
        return $stockItem;
    }

    /**
     * Get single product attribute data 
     *
     * @return Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    public function getProductAttributeByCode($code) {
        $this->_entityAttributeCollection->getSelect()->join(
                    ['eav_entity_type'=>$this->_entityAttributeCollection->getTable('eav_entity_type')],
                    'main_table.entity_type_id = eav_entity_type.entity_type_id',
                    ['entity_type_code'=>'eav_entity_type.entity_type_code']);                
        
        $attribute = $this->_entityAttributeCollection
                        ->setCodeFilter($code)
                        ->addFieldToFilter('entity_type_code', 'catalog_product')
                        ->getFirstItem();
        
        return $attribute;
    }
}