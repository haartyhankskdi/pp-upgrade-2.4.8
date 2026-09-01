<?php

namespace Amasty\Reports\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var Operation\RuleTable
     */
    private $ruleTable;

    /**
     * @var Operation\RuleIndexTable
     */
    private $ruleIndexTable;

    /**
     * @var Operation\UpdateBrandAttribute
     */
    private $updateBrandAttribute;

    /**
     * @var Operation\AbandonedCart
     */
    private $abandonedCart;
    /**
     * @var Operation\UpdateCustomReports
     */
    private $updateCustomReports;

    /**
     * @var Operation\ScheduleReportsNotif
     */
    private $scheduleReportsNotif;

    /**
     * @var Operation\ModifyColumn
     */
    private $modifyColumn;

    public function __construct(
        Operation\RuleTable $ruleTable,
        Operation\RuleIndexTable $ruleIndexTable,
        Operation\UpdateBrandAttribute $updateBrandAttribute,
        Operation\AbandonedCart $abandonedCart,
        Operation\UpdateCustomReports $updateCustomReports,
        Operation\ScheduleReportsNotif $scheduleReportsNotif,
        Operation\ModifyColumn $modifyColumn
    ) {
        $this->ruleTable = $ruleTable;
        $this->ruleIndexTable = $ruleIndexTable;
        $this->updateBrandAttribute = $updateBrandAttribute;
        $this->abandonedCart = $abandonedCart;
        $this->updateCustomReports = $updateCustomReports;
        $this->scheduleReportsNotif = $scheduleReportsNotif;
        $this->modifyColumn = $modifyColumn;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->ruleTable->execute($setup);
            $this->ruleIndexTable->execute($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->updateBrandAttribute->execute($setup);
        }

        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $this->abandonedCart->execute($setup);
        }

        if (version_compare($context->getVersion(), '1.5.0', '<')) {
            $this->updateCustomReports->execute($setup);
        }

        if (version_compare($context->getVersion(), '1.7.0', '<')) {
            $this->scheduleReportsNotif->execute($setup);
        }

        if (version_compare($context->getVersion(), '1.8.0', '<')) {
            $this->modifyColumn->execute($setup);
        }

        $setup->endSetup();
    }
}
