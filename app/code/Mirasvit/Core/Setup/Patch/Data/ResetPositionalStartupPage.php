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
 * @package   mirasvit/module-core
 * @version   1.7.20
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Drop startup page values that point at a positional Mirasvit menu item id.
 *
 * Menu items used to be numbered by position ("Mirasvit_Core::menu::2"), so installing or removing
 * any Mirasvit extension silently repointed a merchant's saved startup page at a different page.
 * When it landed on a config section the admin ended up in an endless redirect, which the merchant
 * cannot undo from the admin - hence clearing it here rather than only fixing the numbering.
 *
 * Removing the row falls back to admin/startup/page (Dashboard). Ids written by the current
 * numbering scheme are left untouched.
 */
class ResetPositionalStartupPage implements DataPatchInterface
{
    private $setup;

    public function __construct(
        ModuleDataSetupInterface $setup
    ) {
        $this->setup = $setup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $connection = $this->setup->getConnection();

        $connection->startSetup();

        $connection->delete(
            $this->setup->getTable('core_config_data'),
            [
                'path = ?'         => 'admin/startup/menu_item_id',
                'value REGEXP ?'   => '^Mirasvit_Core::menu::[0-9]+$',
            ]
        );

        $connection->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
