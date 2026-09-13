<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model\ResourceModel;

class ProductRelatedPost extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magefan_blog_post_relatedproduct', 'related_id');
    }

    /**
     * Get related product ids to which specified item is assigned
     *
     * @param int $productId
     * @return array
     */
    public function lookupRelatedPostIds($productId)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()->from(
            $this->getTable('magefan_blog_post_relatedproduct'),
            'post_id'
        )->where(
            'related_id = ?',
            (int)$productId
        );

        return $adapter->fetchCol($select);
    }

    /**
     * Update related product links
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param array $newRelatedIds
     * @param array $oldRelatedIds
     * @param string $tableName
     * @param string $field
     * @param array $rowData
     * @return void
     */
    public function updateLinks(
        \Magento\Framework\Model\AbstractModel $object,
        array $newRelatedIds,
        array $oldRelatedIds,
        $tableName,
        string $field,
        $rowData = []
    ): void {
        $table = $this->getTable($tableName);

        if ($object->getId() && empty($rowData)) {
            $currentData = $this->_lookupAll($object->getId(), $tableName, '*');
            foreach ($currentData as $item) {
                $rowData[$item[$field]] = $item;
            }
        }

        $insert = $newRelatedIds;
        $delete = $oldRelatedIds;

        if ($delete) {
            $where = ['related_id = ?' => (int)$object->getId(), $field.' IN (?)' => $delete];
            $this->getConnection()->delete($table, $where);
        }

        if ($insert) {
            $data = [];

            foreach ($insert as $id) {
                $id = (int)$id;
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                $data[] = array_merge(
                    ['related_id' => (int)$object->getId(), $field => $id],
                    (isset($rowData[$id]) && is_array($rowData[$id])) ? $rowData[$id] : []
                );
            }

            /* Fix if some rows have extra data */
            $allFields = [];
            foreach ($data as $i => $row) {
                foreach ($row as $key => $value) {
                    $allFields[$key] = $key;
                }
            }
            foreach ($data as $i => $row) {
                foreach ($allFields as $key) {
                    if (!array_key_exists($key, $row)) {
                        $data[$i][$key] = null;
                    }
                }
            }
            /* End fix */

            $this->getConnection()->insertMultiple($table, $data);
        }
    }

    /**
     * Get rows to which specified item is assigned
     *
     * @param  int $postId
     * @param  string $tableName
     * @param  string $field
     * @return array
     */
    protected function _lookupAll($postId, $tableName, $field)
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()->from(
            $this->getTable($tableName),
            $field
        )->where(
            'related_id = ?',
            (int)$postId
        );

        return $adapter->fetchAll($select);
    }
}
