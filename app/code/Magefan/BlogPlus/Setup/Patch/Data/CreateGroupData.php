<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogPlus\Setup\Patch\Data;

use Magento\Framework\Module\ModuleResource;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class CreateGroupData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ModuleResource
     */
    private $moduleResource;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param ModuleResource $moduleResource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        ModuleResource $moduleResource
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $connection =  $this->resourceConnection->getConnection();

        $info = [
            'category_id' =>  $this->resourceConnection->getTableName('magefan_blog_category'),
            'post_id' =>  $this->resourceConnection->getTableName('magefan_blog_post')
        ];
        foreach ($info as $key => $value) {
            $tableName = $value . '_group';
            //phpcs:ignore
            $connection->query("INSERT INTO `$tableName` (`$key`, `group_id`) SELECT `$key`, 0 FROM " . $value .
                ' ON DUPLICATE KEY UPDATE group_id=group_id');
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '2.10.9';
    }
}
