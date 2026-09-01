<?php

namespace Amasty\Reports\Setup\Operation;

use Magento\Framework\Setup\SchemaSetupInterface;

class UpdateBrandAttribute
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function execute(SchemaSetupInterface $setup)
    {
        if ($this->moduleManager->isEnabled('Amasty_ShopbyBrand')) {
            $connection = $setup->getConnection();
            $tableName = $setup->getTable('core_config_data');

            $select = $setup->getConnection()->select()
                ->from($tableName, ['scope', 'scope_id', 'path', 'value'])
                ->where('path = \'amshopby_brand/general/attribute_code\'');

            $settings = $connection->fetchAll($select);

            foreach ($settings as $config) {
                if ($config['value']) {
                    $connection->insertOnDuplicate(
                        $tableName,
                        [
                            'scope_id' => $config['scope_id'],
                            'scope'    => $config['scope'],
                            'value'    => $config['value'],
                            'path'     => 'amasty_reports/general/report_brand'
                        ]
                    );
                }
            }
        }
    }
}
