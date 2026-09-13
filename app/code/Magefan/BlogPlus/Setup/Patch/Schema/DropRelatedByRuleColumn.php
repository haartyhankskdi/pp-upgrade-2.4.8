<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogPlus\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class DropRelatedByRuleColumn implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * Drop the related_by_rule column.
     */
    public function apply(): void
    {
        $connection = $this->schemaSetup->getConnection();
        $table = $this->schemaSetup->getTable("magefan_blog_post_relatedproduct");

        if ($connection->tableColumnExists($table, "related_by_rule")) {
            $connection->dropColumn($table, "related_by_rule");
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [
            \Magefan\BlogPlus\Setup\Patch\Data\MigrateRelatedProductsByRule::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
