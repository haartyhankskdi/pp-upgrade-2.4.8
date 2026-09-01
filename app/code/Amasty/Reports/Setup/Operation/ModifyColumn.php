<?php

declare(strict_types=1);

namespace Amasty\Reports\Setup\Operation;

use Amasty\Reports\Api\Data\NotificationInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

class ModifyColumn
{
    /**
     * @param SchemaSetupInterface $setup
     */
    public function execute(SchemaSetupInterface $setup): void
    {
        $connection = $setup->getConnection();
        $table = $setup->getTable(NotificationInterface::TABLE_NAME);
        $connection->modifyColumn(
            $table,
            NotificationInterface::DISPLAY_PERIOD,
            [
                'type' => Table::TYPE_TEXT,
                'length' => 10,
                'nullable' => false
            ]
        );
    }
}
