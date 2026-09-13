<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Setup\Patch\DeclarativeSchemaApplyBefore;

use Amasty\Customform\Model\ConfigProvider;
use Amasty\Customform\Model\ResourceModel\Form;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FileLinkLifetime implements DataPatchInterface
{
    public const LIFETIME_INIT_VALUE = 0;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    public function __construct(
        Config $config,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->config = $config;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @return FileLinkLifetime
     */
    public function apply()
    {
        if ($this->isNeedApplyPatch()) {
            $this->config->saveConfig(
                ConfigProvider::PATH_PREFIX . ConfigProvider::XML_PATH_FILE_LINK_LIFETIME,
                self::LIFETIME_INIT_VALUE
            );
        }

        return $this;
    }

    private function isNeedApplyPatch(): bool
    {
        return !$this->moduleDataSetup->tableExists(Form::TABLE);
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
