<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Upgrade_1_0_17
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        // improve changes in 1.0.15
        $installer->getConnection()->modifyColumn(
            $installer->getTable('mst_helpdesk_ticket_aggregated_hour'),
            'period',
            [
                'unsigned' => false,
                'nullable' => false,
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'default'  => '0000-00-00 00:00:00',
            ]
        );
    }
}
