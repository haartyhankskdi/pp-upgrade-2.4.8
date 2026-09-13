<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogPlus\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class MigrateRelatedProductsByRule implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Migrate rows where related_by_rule = 1 to new table, then delete them.
     */
    public function apply(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceTable = $this->resourceConnection->getTableName('magefan_blog_post_relatedproduct');
        $targetTable = $this->resourceConnection->getTableName('magefan_blog_post_relatedproduct_by_rule');

        if (!$connection->tableColumnExists($sourceTable, 'related_by_rule')
            || !$connection->isTableExists($targetTable)
        ) {
            return;
        }

        $select = $connection->select()
            ->from($sourceTable, [
                'post_id'    => 'post_id',
                'product_id' => 'related_id',
                'store_id'   => new \Zend_Db_Expr('0'),
            ])
            ->where('related_by_rule = ?', 1);

        $connection->query(
            $connection->insertFromSelect($select, $targetTable, ['post_id', 'product_id', 'store_id'])
        );

        $connection->delete($sourceTable, ['related_by_rule = ?' => 1]);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
