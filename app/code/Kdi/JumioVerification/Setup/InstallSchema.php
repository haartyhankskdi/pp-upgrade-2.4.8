<?php
namespace Kdi\JumioVerification\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
        $table = $installer->getConnection()->newTable(
            $installer->getTable('jumio_verification')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'customer_name',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Customer Name'
        )->addColumn(
            'account_id',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Account ID'
        )->addColumn(
            'workflow_id',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Workflow ID'
        )->addColumn(
            'status',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Status'
        )->addColumn(
            'customer_email',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Customer Email'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT,
            ],
            'Creation Time'
        )->setComment(
            'Jumio Verification Table'
        );

        $installer->getConnection()->createTable($table);
        $installer->getConnection()->addIndex(
            $installer->getTable('jumio_verification'),
            $setup->getIdxName(
                $installer->getTable('jumio_verification'),
                ['customer_name', 'account_id', 'workflow_id', 'status', 'customer_email'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['customer_name', 'account_id', 'workflow_id', 'status', 'customer_email'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
        );
    }
        $installer->endSetup();
    }
}
