<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Utilities;

use Magento\Framework\DB\Select;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\App\ResourceConnection;

class JoinCustomAttribute
{
    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
        $this->resource = $resourceConnection;
    }

    public function execute(
        AbstractCollection $collection,
        string $attributeCode,
        string $tablePrefix = 'main_table'
    ): void {
        $collection->getSelect()->joinLeft(
            ['ea1_' . $attributeCode => $this->getTable('eav_attribute')],
            sprintf('%s.attribute_code = \'%s\'', 'ea1_' . $attributeCode, $attributeCode),
            ['']
        )->joinLeft(
            ['cpei1_' . $attributeCode => $this->getIndexEavSelect($collection->getConnection())],
            sprintf(
                '%2$s.product_id = %1$s.' . $this->getIndexEavColumn()
                . ' AND %2$s.store_id = %1$s.store_id AND '
                . '%1$s.attribute_id = %3$s.attribute_id',
                'cpei1_' . $attributeCode,
                $tablePrefix,
                'ea1_' . $attributeCode
            ),
            ['']
        )->joinLeft(
            ['eaov1_' . $attributeCode => $this->getTable('eav_attribute_option_value')],
            sprintf(
                '%1$s.value = %2$s.option_id AND %2$s.store_id = 0',
                'cpei1_' . $attributeCode,
                'eaov1_' . $attributeCode
            ),
            ['']
        )->group(
            sprintf('IF (%1$s.value IS NOT NULL, %1$s.value, 0)', 'cpei1_' . $attributeCode)
        );
    }

    private function getIndexEavSelect(): Select
    {
        return $this->connection->select()->from(
            $this->getTable('catalog_product_index_eav')
        )->group(
            'source_id'
        )->group(
            'attribute_id'
        )->group(
            'store_id'
        )->group(
            'value'
        );
    }

    private function getIndexEavColumn(): string
    {
        $eavColumn = 'entity_id';
        $columns = array_keys($this->connection->describeTable($this->getTable('catalog_product_index_eav')));
        if (in_array('source_id', $columns)) {
            $eavColumn = 'source_id';
        }

        return $eavColumn;
    }

    private function getTable(string $table): string
    {
        return $this->resource->getTableName($table);
    }
}
