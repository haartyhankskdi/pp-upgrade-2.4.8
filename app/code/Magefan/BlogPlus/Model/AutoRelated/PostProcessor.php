<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model\AutoRelated;

class PostProcessor extends AbstractProcessor
{
    /**
     * @var string
     */
    protected $autoRelatedTable = 'magefan_blog_post_relatedpost';

    /**
     * @var string
     */
    protected $autoRelatedLogTable = 'magefan_blog_post_relatedpost_log';

    /**
     * Get related post ids
     *
     * @param \Magefan\Blog\Model\Post $post
     * @return array
     */
    protected function getRelatedIds($post)
    {
        $ids = $post->getRelatedPosts()->getAllIds();
        $ids[] = $post->getId();

        return $ids;
    }

    /**
     * Get auto related post ids from post
     *
     * @param \Magefan\Blog\Model\Post $post
     * @return mixed[]
     */
    protected function getAutoRelatedIds(
        \Magefan\Blog\Model\Post $post
    ): array {
        $collection = $this->postCollectionFactory->create();
        $resource = $collection->getResource();
        $connection = $collection->getResource()->getConnection();

        $vars = [
            'content' => $this->extractKeywords($post->getContent()),
            'title' => $this->extractKeywords($post->getTitle()),
            'post_id' => $post->getId()
        ];

        $where = 'post_id <> :post_id AND (0
          + (MATCH (content) AGAINST (:content)) * 1
          + (MATCH (title) AGAINST (:title)) * 3) > 2';

        $select = $connection->select()
            ->from(['main_table' => $resource->getTable('magefan_blog_post')])
            ->columns(['search_rate' => '(0
              + (MATCH (content) AGAINST (:content)) * 1
              + (MATCH (title) AGAINST (:title)) * 3)'])
            ->where($where)
            ->group('main_table.post_id')
            ->order('search_rate ' .  \Magento\Framework\Api\SortOrder::SORT_DESC)
            ->limit($this->limit);

        $result = $connection->fetchAll($select, $vars);

        $ids = [];
        if (count($result)) {
            foreach ($result as $item) {
                $ids[] = $item['post_id'];
            }
        }

        return $ids;
    }

    /**
     * Retrieve tru if can generate auto related items
     *
     * @return bool
     */
    protected function autoRelatedEnabled(): bool
    {
        return $this->config->isAutoRelatedPostsEnabled();
    }

    /**
     * Get array of black words from Abstract Processor and from admin panel and join two arrays
     *
     * @return array
     */
    protected function getIgnoredWords(): array
    {
        return array_merge(
            parent::getIgnoredWords(),
            $this->config->getAutoRelatedPostsBlackWords()
        );
    }
}
