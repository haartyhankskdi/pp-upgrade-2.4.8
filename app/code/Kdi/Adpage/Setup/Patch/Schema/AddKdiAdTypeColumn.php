<?php
namespace Kdi\Adpage\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\DB\Ddl\Table;

class AddKdiAdTypeColumn implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Constructor
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Apply the patch
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();
        $tableName = $setup->getTable('cms_page');

        // Add column only if it doesn’t already exist
        if ($connection->isTableExists($tableName) &&
            !$connection->tableColumnExists($tableName, 'kdi_ad_type')) {

            $connection->addColumn(
                $tableName,
                'kdi_ad_type',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 50,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Page Type (Google Ad / CMS Page)',
                ]
            );
        }

        $setup->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
