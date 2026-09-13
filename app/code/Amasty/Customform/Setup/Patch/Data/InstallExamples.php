<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Setup\Patch\Data;

use Amasty\Customform\Model\ResourceModel\Form;
use Amasty\Customform\Setup\Model\FormExamplesInstaller;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class InstallExamples implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var FormExamplesInstaller
     */
    private $formsExamplesInstaller;

    /**
     * @var AppState
     */
    private $appState;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        FormExamplesInstaller $formsExamplesInstaller,
        AppState $appState
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->formsExamplesInstaller = $formsExamplesInstaller;
        $this->appState = $appState;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): InstallExamples
    {
        if ($this->isCanApply()) {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this->formsExamplesInstaller, 'installExamples']
            );
        }

        return $this;
    }

    private function isCanApply(): bool
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select();
        $select->from($this->moduleDataSetup->getTable(Form::TABLE), []);
        $select->columns(new \Zend_Db_Expr('COUNT(*)'));

        return !$connection->fetchOne($select);
    }
}
