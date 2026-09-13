<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model\ResourceModel\Post\Collection;

/**
 * Blog post export collection
 */
class Export extends \Magefan\Blog\Model\ResourceModel\Post\Collection
{

    /**
     * Perform operations after collection load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $result = parent::_afterLoad();
        foreach ($this as $item) {
            $resourse = $item->getResource();
            $adapter =  $resourse->getConnection();

            foreach (['product', 'post'] as $type) {
                $select = $adapter->select()->from(
                    $this->getTable('magefan_blog_post_related' .  $type)
                )->where(
                    'post_id = ?',
                    (int)$item->getId()
                );

                $rows = $adapter->fetchAll($select);
                if ($rows) {
                    $relatedIds = [];
                    foreach ($rows as $row) {
                        if (isset($row['related_id'])) {
                            $relatedIds[] = (int)$row['related_id'];
                        }
                    }

                    $item->setData('related_' . $type . 's', json_encode($relatedIds));
                }
            }
        }
        return $result;
    }
}
