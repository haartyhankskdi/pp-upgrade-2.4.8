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

use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\DB\FieldToConvert;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    private $config;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * UpgradeData constructor.
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $config
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     */
    public function __construct(
        \Magento\Framework\App\Config\Storage\WriterInterface $config,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        $this->config          = $config;
        $this->objectManager   = $objectManager;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @inheritdoc
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.11', '<')) {
            if (version_compare($this->productMetadata->getVersion(), '2.2.2', '>=')) {
                $this->convertSerializedDataToJson($setup);
            }
        }
        if (version_compare($context->getVersion(), '1.0.12', '<')) {
            if (version_compare($this->productMetadata->getVersion(), '2.3.3', '==')) {
                $data = [
                    ['from' => 'Ỳ', 'to' => 'y'],
                    ['from' => 'Ǹ', 'to' => 'n'],
                    ['from' => 'Ẁ', 'to' => 'w'],
                ];
                foreach ($data as $k => $v) {
                    $this->config->save('url/convert/'.$k.'/from', $v['from'], 'default');
                    $this->config->save('url/convert/'.$k.'/to', $v['to'], 'default');
                }
            }
        }

        $setup->endSetup();
    }

    /**
     * Convert metadata from serialized to JSON format:
     *
     * @param ModuleDataSetupInterface $setup
     *
     * @return void
     */
    public function convertSerializedDataToJson($setup)
    {
        /** @var \Magento\Framework\DB\AggregatedFieldDataConverter $aggregatedFieldConverter */
        $aggregatedFieldConverter = $this->objectManager->get("\Magento\Framework\DB\AggregatedFieldDataConverter");
        $aggregatedFieldConverter->convert(
            [
                new FieldToConvert(
                    SerializedToJson::class,
                    $setup->getTable('mst_helpdesk_rule'),
                    "rule_id",
                    'conditions_serialized'
                ),
            ],
            $setup->getConnection()
        );
    }
}
