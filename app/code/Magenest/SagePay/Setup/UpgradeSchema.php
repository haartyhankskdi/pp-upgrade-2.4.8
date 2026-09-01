<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table as Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('magenest_sagepay_subscription_profile'),
                'sequence_order_ids',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Sequential Order ID'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $table = $setup->getConnection()->newTable($setup->getTable('magenest_sagepay_saved_card'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Customer ID'
                )
                ->addColumn(
                    'customer_email',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Customer Email'
                )
                ->addColumn(
                    'card_id',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Card ID'
                )
                ->addColumn(
                    'last_4',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Last 4 number'
                )
                ->setComment('Customer card ID');
            $setup->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '1.1.1') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('magenest_sagepay_saved_card'),
                'card_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Card type',
                    'size' => 30
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('magenest_sagepay_saved_card'),
                'expire_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'comment' => 'Card type',
                    'size' => 10
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable('magenest_sagepay_saved_card'),
                'created_at',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'comment' => 'Created At',
                    'size' => null,
                    'default' => Table::TIMESTAMP_INIT
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.6.8') < 0) {
            $this->alterTransactionTable($setup);
        }

        if (version_compare($context->getVersion(), '1.6.9') < 0) {
            $this->alterTransactionTable1($setup);
        }

        if (version_compare($context->getVersion(), '1.7.2') < 0) {
            $this->alterTransactionTableAddCustomerEmail($setup);
        }

        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('magenest_sagepay_transaction'),
                'vendor_tx_code',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => null,
                    'default' => null,
                    'comment' => 'VendorTxCode'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.0.4') < 0) {
            $setup->getConnection()->changeColumn(
                $setup->getTable('magenest_sagepay_transaction'),
                'customer_id',
                'customer_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'length' => 11,
                    'comment' => 'Customer ID'
                ]
            );
        }

        $setup->endSetup();
    }

    private function alterTransactionTableAddCustomerEmail($setup){
        $setup->getConnection()->addColumn(
            $setup->getTable('magenest_sagepay_transaction'),
            'customer_email',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'default' => "",
                'comment' => 'Customer Email'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('magenest_sagepay_transaction'),
            'quote_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => null,
                'comment' => 'Quote Id',
                'nullable' => false,
            ]
        );
        $setup->getConnection()->changeColumn(
            $setup->getTable('magenest_sagepay_transaction'),
            'order_id',
            'order_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => null,
                'comment' => 'Order ID',
                'nullable' => true,
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable('magenest_sagepay_transaction'),
            'response_data',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => null,
                'comment' => 'Sage response data',
            ]
        );
    }

    private function alterTransactionTable1($setup){
        $setup->getConnection()->addColumn(
            $setup->getTable('magenest_sagepay_transaction'),
            'created_at',
            [
                'type' => Table::TYPE_TIMESTAMP,
                'length' => null,
                'default' => Table::TIMESTAMP_INIT,
                'comment' => 'Created At'
            ]
        );
    }

    private function alterTransactionTable($setup){
        $setup->getConnection()->changeColumn(
            $setup->getTable('magenest_sagepay_transaction'),
            'transaction_id',
            'transaction_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'comment' => 'Transaction ID',
                'nullable' => false,
            ]
        );

        $setup->getConnection()->changeColumn(
            $setup->getTable('magenest_sagepay_transaction'),
            'order_id',
            'order_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => null,
                'comment' => 'Order ID',
                'nullable' => false,
            ]
        );

        $setup->getConnection()->addIndex(
            $setup->getTable('magenest_sagepay_transaction'),
            $setup->getIdxName(
                'magenest_sagepay_transaction',
                'transaction_id'
            ),
            'transaction_id'
        );
    }
}
