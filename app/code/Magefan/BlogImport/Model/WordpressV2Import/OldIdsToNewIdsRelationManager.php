<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model\WordpressV2Import;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class OldIdsToNewIdsRelationManager
{
    public const TABLE = 'magefan_blog_import_old_new_ids';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->connection = $this->resourceConnection->getConnection();
        $this->logger = $logger;
    }

    /**
     * Get map
     *
     * @param string $entityType
     * @return array
     */
    public function getMap(string $entityType): array
    {
        $tableName = $this->resourceConnection->getTableName(self::TABLE);

        $select = $this->connection->select()
            ->from($tableName, ['old_id', 'new_id'])
            ->where('type = ?', $entityType);

        return $this->connection->fetchPairs($select);
    }

    /**
     * Get MfBlogIds by WpIds
     *
     * @param array $wpIds
     * @param string $entityType
     * @return array
     */
    public function getMfBlogIdsByWpIds(array $wpIds, string $entityType): array
    {
        $tableName = $this->resourceConnection->getTableName(self::TABLE);

        $select = $this->connection->select()
            ->from($tableName, 'new_id')
            ->where('old_id IN (?)', $wpIds)
            ->where('type = ?', $entityType)
            ->order('new_id DESC');

        return $this->connection->fetchCol($select);
    }

    /**
     * Update map
     *
     * @param array $map
     * @param string $entityType
     * @return void
     */
    public function updateMap(array $map, string $entityType)
    {
        foreach ($map as $wpId => $mfBlogId) {
            $this->save((int) $wpId, (int) $mfBlogId, $entityType);
        }
    }

    /**
     * Save relation
     *
     * @param int $wpId
     * @param int $mfBlogId
     * @param string $entityType
     * @return void
     */
    private function save(int $wpId, int $mfBlogId, string $entityType)
    {
        $tableName = $this->resourceConnection->getTableName(self::TABLE);

        try {
            $this->connection->delete($tableName, [
                'old_id = ?' => $wpId,
                'type = ?' => $entityType
            ]);

            $this->connection->insert($tableName, [
                'old_id' => $wpId,
                'new_id' => $mfBlogId,
                'type' => $entityType
            ]);

        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
