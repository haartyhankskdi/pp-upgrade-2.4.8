<?php
/**
 * Copyright © 2020 Magenest. All rights reserved.
 * See COPYING.txt for license details.
 *
 * sage extension
 * NOTICE OF LICENSE
 *
 * @category Magenest
 * @package sage
 * @time: 14/08/2020 08:27
 */

namespace Magenest\SagePay\Setup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    const MD_CODE = 'md_code';

    /**
     * @var QuoteSetupFactory
     */
    protected $quoteSetupFactory;

    /**
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @param QuoteSetupFactory $quoteSetupFactory
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        QuoteSetupFactory $quoteSetupFactory,
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * @inheritDoc
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.6') < 0) {
            $this->addMdCodeAttribute($setup);
        }

    }

    private function addMdCodeAttribute(ModuleDataSetupInterface $setup)
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesInstaller */
        $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);

        $setup->startSetup();
        $salesInstaller->addAttribute(\Magento\Sales\Model\Order::ENTITY, self::MD_CODE, ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length'=> 255, 'visible' => false, 'nullable' => true,]);

        $setup->endSetup();
    }
}
