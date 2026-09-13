<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Plugin\Setup\Model\DeclarationInstaller;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Declaration\Schema\DryRunLogger;
use Magento\Framework\Setup\Patch\PatchApplier;
use Magento\Framework\Setup\Patch\PatchHistory;
use Magento\Setup\Model\DeclarationInstaller;

class ApplyPatchesBeforeDeclarativeSchema
{
    public const MODULE_NAME = 'Amasty_Customform';

    /**
     * @var PatchApplier
     */
    private $patchApplier;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        PatchApplier $patchApplier,
        ResourceConnection $resourceConnection
    ) {
        $this->patchApplier = $patchApplier;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param DeclarationInstaller $declarationInstaller
     * @param array $request
     * @return array|null
     * @throws \Magento\Framework\Setup\Exception
     */
    public function beforeInstallSchema(
        DeclarationInstaller $declarationInstaller,
        array $request
    ): ?array {
        $isDryRun = $request[DryRunLogger::INPUT_KEY_DRY_RUN_MODE] ?? true;
        $connection = $this->resourceConnection->getConnection();

        if (!$isDryRun
            && $connection->isTableExists($this->resourceConnection->getTableName(PatchHistory::TABLE_NAME))
        ) {
            $this->patchApplier->applySchemaPatch(self::MODULE_NAME);
        }

        return null;
    }
}
