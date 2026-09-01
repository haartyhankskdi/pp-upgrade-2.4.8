<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Filters;

use Amasty\Reports\Model\ConfigProvider;
use Magento\Framework\DB\Select;
use Magento\Framework\Data\Collection\AbstractDb;

class AddOrderStatusFilter
{
    public const ORDER_TABLE_NAME = 'sales_order';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function execute(AbstractDb $collection, string $tableAlias = 'main_table'): void
    {
        $statuses = $this->configProvider->getOrderStatuses();
        if (empty($statuses)) {
            return;
        }

        $fromPart = $collection->getSelect()->getPart(Select::FROM);
        $orderTable = $collection->getTable(self::ORDER_TABLE_NAME);

        if (!isset($fromPart[$tableAlias]['tableName'])) {
            return;
        }

        $statusTable = $tableAlias;
        $tableName = $fromPart[$tableAlias]['tableName'];
        if (!isset($fromPart[$orderTable])
            && $tableName != $orderTable
            && !isset($fromPart[self::ORDER_TABLE_NAME])
        ) {
            $tableWithOrderId = $tableName == $collection->getTable('catalog_product_entity')
                ? 'sales_order_item'
                : 'main_table';
            $collection->getSelect()
                ->joinLeft(
                    ['order_table' => $orderTable],
                    'order_table.entity_id = ' . $tableWithOrderId . '.order_id'
                );
            $statusTable = 'order_table';
        }

        if (isset($fromPart['sales_order'])) {
            $statusTable = 'sales_order'; // product perfomance report
        }

        $collection->addFieldToFilter($statusTable . '.status', ['in' => $statuses]);
    }
}
