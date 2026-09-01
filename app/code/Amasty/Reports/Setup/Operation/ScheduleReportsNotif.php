<?php

declare(strict_types=1);

namespace Amasty\Reports\Setup\Operation;

use Amasty\Reports\Api\Data\NotificationInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class ScheduleReportsNotif
{
    public function execute(SchemaSetupInterface $setup): void
    {
        $tableName = $setup->getTable(NotificationInterface::TABLE_NAME);

        $table = $setup->getConnection()
            ->newTable($tableName)
            ->addColumn(
                NotificationInterface::ENTITY_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Entity ID'
            )->addColumn(
                NotificationInterface::NAME,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Notification Title'
            )
            ->addColumn(
                NotificationInterface::REPORTS,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Reports'
            )
            ->addColumn(
                NotificationInterface::STORE_IDS,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Store Ids'
            )
            ->addColumn(
                NotificationInterface::INTERVAL_QTY,
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false],
                'Interval Qty'
            )
            ->addColumn(
                NotificationInterface::INTERVAL,
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false],
                'Interval'
            )
            ->addColumn(
                NotificationInterface::DISPLAY_PERIOD,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'length' => 10],
                'Display Period'
            )
            ->addColumn(
                NotificationInterface::RECEIVER,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Email Receiver'
            )
            ->addColumn(
                NotificationInterface::FREQUENCY,
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false],
                'Frequency'
            )
            ->addColumn(
                NotificationInterface::CRON_SCHEDULE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                ['nullable' => false, 'default' => ''],
                'Cron Schedule'
            )
            ->setComment(
                'Amasty Reports Schedule Notifications Table'
            );
        $setup->getConnection()->createTable($table);
    }
}
