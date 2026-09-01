<?php

namespace Amasty\Customform\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddColumn implements SchemaPatchInterface
{
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable('sales_order'),
            'questionnaire_unique_id',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment'  => 'Questionnaire Unique Hash Id',
                'grid' => true
            ]
        );

        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable('amasty_customform_answer'),
            'questionnaire_unique_id',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment'  => 'Questionnaire Unique Hash Id',
                'grid' => true
            ]
        );
        $this->moduleDataSetup->endSetup();
    }
}
