<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

/**
 * Class InstallSchema
 *
 * @package Mageplaza\PromoBanner\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @throws                   Zend_Db_Exception
     * @SuppressWarnings(Unused)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('mageplaza_promobanner_banners')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mageplaza_promobanner_banners'))
                ->addColumn('banner_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ], 'Banner Id')
                ->addColumn('name', Table::TYPE_TEXT, 255, [], 'Name')
                ->addColumn('status', Table::TYPE_INTEGER, 1, ['nullable' => false], 'Status')
                ->addColumn('store_ids', Table::TYPE_TEXT, 255, ['nullable' => false], 'Store')
                ->addColumn('customer_group_ids', Table::TYPE_TEXT, 255, ['nullable' => false], 'Customer Group')
                ->addColumn('category', Table::TYPE_TEXT, 255, [], 'Category')
                ->addColumn('from_date', Table::TYPE_DATETIME, null, [], 'From Date')
                ->addColumn('to_date', Table::TYPE_DATETIME, null, [], 'To Date')
                ->addColumn('priority', Table::TYPE_INTEGER, null, ['nullable' => false], 'Priority')
                ->addColumn('type', Table::TYPE_TEXT, 255, [], 'Display Type')
                ->addColumn('banner_image', Table::TYPE_TEXT, 255, [], 'Banner Image')
                ->addColumn('slider_images', Table::TYPE_TEXT, '2M', [], 'Slider Images')
                ->addColumn('cms_block_id', Table::TYPE_INTEGER, null, [], 'CMS block')
                ->addColumn('content', Table::TYPE_TEXT, '2M', [], 'Content')
                ->addColumn('popup_image', Table::TYPE_TEXT, 255, [], 'Popup Image')
                ->addColumn('popup_responsive', Table::TYPE_TEXT, 255, [], 'Popup Responsive')
                ->addColumn('floating_image', Table::TYPE_TEXT, 255, [], 'Floating Image')
                ->addColumn('url', Table::TYPE_TEXT, 255, [], 'Url')
                ->addColumn('position', Table::TYPE_TEXT, 255, [], 'Display position')
                ->addColumn('floating_position', Table::TYPE_TEXT, 255, [], 'Display floating position')
                ->addColumn('page', Table::TYPE_INTEGER, 1, ['nullable' => false], 'Page')
                ->addColumn('page_type', Table::TYPE_TEXT, 255, [], 'Page type')
                ->addColumn('category_ids', Table::TYPE_TEXT, 255, [], 'Category page')
                ->addColumn('conditions_serialized', Table::TYPE_TEXT, '2M', [], 'Conditions Serialized')
                ->addColumn('actions_serialized', Table::TYPE_TEXT, '2M', [], 'Actions Serialized')
                ->addColumn('show_product_page', Table::TYPE_INTEGER, 1, ['nullable' => false], 'Status')
                ->addColumn('auto_close_time', Table::TYPE_TEXT, 255, [], 'Auto Close PromoBanner')
                ->addColumn('auto_reopen_time', Table::TYPE_TEXT, 255, [], 'Auto Re-Open PromoBanner')
                ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [
                    'nullable' => false,
                    'default'  => Table::TIMESTAMP_INIT
                ], 'Creation Time')
                ->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, [
                    'nullable' => false,
                    'default'  => Table::TIMESTAMP_INIT_UPDATE
                ], 'Update Time')
                ->addIndex(
                    $installer->getIdxName('mageplaza_promobanner_banners', ['status', 'priority']),
                    ['status', 'priority']
                );

            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('mageplaza_promobanner_actions_index')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('mageplaza_promobanner_actions_index'))
                ->addColumn('banner_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false
                ], 'Banner Id')
                ->addColumn('product_id', Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false
                ], 'Product Id')
                ->addIndex(
                    $installer->getIdxName('mageplaza_promobanner_actions_index', [
                        'banner_id',
                        'product_id'
                    ], AdapterInterface::INDEX_TYPE_UNIQUE),
                    ['banner_id', 'product_id'],
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'mageplaza_promobanner_actions_index',
                        'banner_id',
                        'mageplaza_promobanner_banners',
                        'banner_id'
                    ),
                    'banner_id',
                    $installer->getTable('mageplaza_promobanner_banners'),
                    'banner_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $installer->getFkName(
                        'mageplaza_promobanner_actions_index',
                        'product_id',
                        'catalog_product_entity',
                        'product_id'
                    ),
                    'product_id',
                    $installer->getTable('catalog_product_entity'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('Mageplaza Promo Banner Actions Index');

            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
