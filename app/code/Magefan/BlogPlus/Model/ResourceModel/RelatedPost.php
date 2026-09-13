<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model\ResourceModel;

class RelatedPost extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magefan_blog_post_relatedpost_log', 'post_id');
    }

    /**
     * Update related post links
     *
     * @param array $newRelatedIds
     * @param string $tableName
     * @param string $field
     */
    public function _updateLinks(
        array $newRelatedIds,
        $tableName,
        string $field
    ): void {
        $table = $this->getTable($tableName);

        $post_id = [];
        foreach ($newRelatedIds as $post) {
            $post_id[] = $post['post_id'];
        }
        $oldIds = $this->lookupRelatedPostIds($post_id);

        $insert = $newRelatedIds;
        $delete = $oldIds;
        if ($delete) {
            $where = ['post_id = ?' => $post_id, $field.' IN (?)' => $delete];

            $this->getConnection()->delete($table, $where);
        }

        if ($insert) {
            $this->getConnection()->insertMultiple($table, $insert);
        }
    }

    /**
     * Get related post ids to which specified item is assigned
     *
     * @param int $postId
     * @return array
     */
    public function lookupRelatedPostIds($postId)
    {
        return $this->_lookupIds($postId, 'magefan_blog_post_relatedpost', 'related_id');
    }

    /**
     * Get ids to which specified item is assigned
     *
     * @param  int $postId
     * @param  string $tableName
     * @param  string $field
     * @return array
     */
    protected function _lookupIds($postId, $tableName, $field)
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()->from(
            $this->getTable($tableName),
            $field
        )->where(
            'post_id = ?',
            (int)$postId
        );

        return $adapter->fetchCol($select);
    }
}
