<?php

declare(strict_types=1);

namespace Amasty\Reports\Traits;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Images;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

trait ImageTrait
{
    /**
     * @param AbstractCollection $collection
     * @param string $productIdTable
     */
    private function joinThumbnailAttribute($collection, string $productIdTable = 'main_table'): void
    {
        $collection->getSelect()->joinLeft(
            ['attributes' => $this->getJoinAttribute($collection->getConnection())],
            sprintf('%s.product_id = attributes.product_id', $productIdTable),
            ['value' => 'attributes.value']
        );
    }

    private function getJoinAttribute(AdapterInterface $connection): \Magento\Framework\DB\Select
    {
        $attributeId = $this->eavAttribute->getIdByCode(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            Images::CODE_THUMBNAIL
        );
        $productIdRow = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();

        return $connection->select()
            ->from(['product_entity' => $this->getTable('catalog_product_entity')])
            ->join(
                ['product_entity_varchar' => $this->getTable('catalog_product_entity_varchar')],
                sprintf(
                    'product_entity.%s = product_entity_varchar.%s
                    AND product_entity_varchar.attribute_id = %s
                    AND product_entity_varchar.store_id = 0',
                    $productIdRow,
                    $productIdRow,
                    $attributeId
                ),
                ['product_id' => 'product_entity.entity_id', 'value' => 'product_entity_varchar.value']
            );
    }
}
