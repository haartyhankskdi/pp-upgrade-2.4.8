<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model;

use Magento\Framework\Config\ConfigOptionsListConstants;
use Magefan\Blog\Model\Post as MagefanPost;

/**
 * Mageplaza import model
 */
class Mageplaza extends AbstractImport
{
    /**
     * @var null
     */
    private $connection = null;

    /**
     * @var null
     */
    private $srcConnection = null;

    /**
     * @var null
     */
    private $srcTablePrefix = null;
    /**
     * @var null
     */
    private $oldCategories = null;

    /**
     * @var null
     */
    private $oldTags = null;

    /**
     * @var null
     */
    private $oldAuthors = null;

    /**
     * Execute import.
     *
     * @return void
     * @throws \Exception
     */
    public function execute(): void
    {
        try {
            $this->srcConnection = $this->getDbConnection();
            $this->srcTablePrefix = $this->getPrefix();

            $this->connection = $this->getConnection();
            $this->connection->beginTransaction();

            $this->checkConnection(
                $this->srcConnection,
                $this->srcTablePrefix . 'mageplaza_blog_category',
                'Mageplaza Blog Extension not detected.'
            );

            $this->importCategory();
            $this->importTag();
            $this->importAuthor();
            $this->importPost();

            $this->connection->commit();

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->connection->rollBack();

            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Import categories.
     *
     * @return void
     * @throws \Exception
     */
    private function importCategory(): void
    {
        $categories = [];
        $this->oldCategories = [];

        $select = $this->srcConnection->select()
            ->from(['t' => $this->srcTablePrefix . 'mageplaza_blog_category'], [
                'old_id' => 'category_id',
                'title' => 'name',
                'identifier' => 'url_key',
                'position' => 'position',
                'content' => 'description',
                'parent_id' => 'parent_id',
                'is_active' => 'enabled',
                'store_ids' => 'store_ids',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'meta_robots'
            ]);

        $mpBlogCategories = $this->srcConnection->fetchAll($select);

        foreach ($mpBlogCategories as $mpBlogCategory) {
            /* Prepare category data */

            $mpBlogCategory['store_ids'] = explode(',', $mpBlogCategory['store_ids']);
            $mpBlogCategory['path'] = 0;
            $mpBlogCategory['include_in_menu'] = 1;

            $category = $this->_categoryFactory->create();

            try {
                /* Initial saving */
                $category->setData($mpBlogCategory)->save();
                $this->_importedCategoriesCount++;
                $categories[$category->getId()] = $category;
                $this->oldCategories[$category->getOldId()] = $category;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($category);
                $this->_skippedCategories[] = $mpBlogCategory['title'];
                $this->_logger->debug('Blog Category Import [' . $mpBlogCategory['title'] . ']: '. $e->getMessage());
            }
        }

        /* Reindexing parent categories */
        foreach ($categories as $ct) {
            if ($oldParentId = $ct->getData('parent_id')) {
                if (isset($this->oldCategories[$oldParentId])) {
                    $ct->setPath(
                        $parentId = $this->oldCategories[$oldParentId]->getId()
                    );
                }
            }
        }

        for ($i = 0; $i < 4; $i++) {
            $changed = false;
            foreach ($categories as $ct) {
                if ($ct->getPath()) {
                    $parentId = explode('/', $ct->getPath())[0];
                    $pt = $categories[$parentId];
                    if ($pt->getPath()) {
                        $ct->setPath($pt->getPath() . '/'. $ct->getPath());
                        $changed = true;
                    }
                }
            }

            if (!$changed) {
                break;
            }
        }
        /* end*/

        foreach ($categories as $ct) {
            /* Final saving */
            $ct->save();
        }
    }

    /**
     * Import tags.
     *
     * @return void
     * @throws \Exception
     */
    private function importTag(): void
    {
        $tags = [];
        $this->oldTags = [];
        $existingTags = [];

        $select = $this->srcConnection->select()
            ->from(['t' => $this->srcTablePrefix . 'mageplaza_blog_tag'], [
                'old_id' => 'tag_id',
                'title' => 'name',
                'identifier' => 'url_key',
                'content' => 'description',
                'is_active' => 'enabled',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'meta_robots'
            ]);

        $mpBlogTags = $this->srcConnection->fetchAll($select);

        foreach ($mpBlogTags as $mpBlogTag) {
            if (!$mpBlogTag['title']) {
                continue;
            }

            $mpBlogTag['title'] = trim($mpBlogTag['title']);

            try {
                /* Initial saving */
                if (!isset($existingTags[$mpBlogTag['title']])) {
                    $tag = $this->_tagFactory->create();
                    $tag->setData($mpBlogTag)->save();
                    $this->_importedTagsCount++;
                    $tags[$tag->getId()] = $tag;
                    $this->oldTags[$tag->getOldId()] = $tag;
                    $existingTags[$tag->getTitle()] = $tag;
                } else {
                    $tag = $existingTags[$mpBlogTag['title']];
                    $this->oldTags[$mpBlogTag['old_id']] = $tag;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedTags[] = $mpBlogTag['title'];
                $this->_logger->debug('Blog Tag Import [' . $mpBlogTag['title'] . ']: '. $e->getMessage());
            }
        }
    }

    /**
     * Import authors.
     *
     * @return void
     * @throws \Exception
     */
    private function importAuthor(): void
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if (!$objectManager->get(\Magento\Framework\Module\Manager::class)->isEnabled('Magefan_BlogAuthor')) {
            return;
        }

        $authors = [];
        $this->oldAuthors = [];
        $existingAuthors = [];

        $select = $this->srcConnection->select()
            ->from(['t' => $this->srcTablePrefix . 'mageplaza_blog_author'], [
                'old_id' => 'user_id',
                'name',
                'featured_img' => 'image',
                'content' => 'short_description',
                'email',
                'facebook_page_url' => 'facebook_link',
                'twitter_page_url' => 'twitter_link'
            ]);

        $mpBlogAuthors = $this->srcConnection->fetchAll($select);

        foreach ($mpBlogAuthors as $mpBlogAuthor) {
            if (empty($mpBlogAuthor['email'])) {
                continue;
            }

            $mpBlogAuthor['email'] = trim((string)$mpBlogAuthor['email']);

            try {
                /* Initial saving */
                if (!isset($existingAuthors[$mpBlogAuthor['email']])) {
                    $author = $this->_authorFactory->create();

                    $mpBlogAuthor['firstname'] = $mpBlogAuthor['name'];
                    $mpBlogAuthor['lastname'] = $mpBlogAuthor['name'];

                    $author->setData($mpBlogAuthor)->save();

                    $this->_importedAuthorsCount++;
                    $authors[$author->getId()] = $author;
                    $this->oldAuthors[$author->getOldId()] = $author;
                    $existingAuthors[$author->getEmail()] = $author;
                } else {
                    $author = $existingAuthors[$mpBlogAuthor['email']];
                    $this->oldAuthors[$mpBlogAuthor['old_id']] = $author;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedAuthors[] = $mpBlogAuthor['email'];
                $this->_logger->debug('Blog Author Import [' . $mpBlogAuthor['email'] . ']: '. $e->getMessage());
            }
        }
    }

    /**
     * Import posts.
     *
     * @return void
     * @throws \Exception
     */
    private function importPost(): void
    {
        $select = $this->srcConnection->select()->from($this->srcTablePrefix . 'mageplaza_blog_post');
        $mpBlogPosts = $this->srcConnection->fetchAll($select);

        foreach ($mpBlogPosts as $mpBlogPost) {
            $mpBlogPostId = (int)$mpBlogPost['post_id'];

            $postCategories = $this->getPostCategories($mpBlogPostId);
            $postTags = $this->getPostTags($mpBlogPostId);

            /* Find store ids */
            $mpBlogPost['store_ids'] = explode(',', $mpBlogPost['store_ids']);

            /* Prepare post data */
            $mpBlogPost = [
                'old_id'            => $mpBlogPost['post_id'],
                'store_ids'         => $mpBlogPost['store_ids'],
                'title'             => $mpBlogPost['name'],
                'meta_title'        => $mpBlogPost['meta_title'],
                'meta_keywords'     => $mpBlogPost['meta_keywords'],
                'meta_description'  => $mpBlogPost['meta_description'],
                'meta_robots'  => $mpBlogPost['meta_robots'],
                'identifier'        => $mpBlogPost['url_key'],
                'content_heading'   => '',
                'content'           => $mpBlogPost['post_content'],
                'short_content'     => $mpBlogPost['short_description'],
                'creation_time'     => strtotime((string)$mpBlogPost['created_at']),
                'update_time'       => strtotime((string)$mpBlogPost['updated_at']),
                'publish_time'      => strtotime((string)$mpBlogPost['publish_date']),
                'is_active'         => $this->getMfPostStatus((int)$mpBlogPost['enabled']),
                'categories'        => $postCategories,
                'tags'              => $postTags,
                'featured_img'      => !empty($mpBlogPost['image']) ? 'magefan_blog/' . $mpBlogPost['image'] : '',
                'author_id'         => isset($this->oldAuthors[$mpBlogPost['author_id']])
                                        ? $this->oldAuthors[$mpBlogPost['author_id']]->getId()
                                        : null,
                'views_count'       => (int)$mpBlogPost['views'],
                'enable_comments'   => (int)$mpBlogPost['allow_comment'],
            ];

            $post = $this->_postFactory->create();

            try {
                /* Post saving */
                $post->setData($mpBlogPost)->save();
                $this->_importedPostsCount++;

                $this->importPostComments((int)$post->getOldId(), (int)$post->getId());
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedPosts[] = $mpBlogPost['title'];

                $this->_logger->debug('Blog Post Import [' . $mpBlogPost['title'] . ']: '. $e->getMessage());
            }
            unset($post);
        }
        /* end */
    }

    /**
     * Import post comments.
     *
     * @param int $mpBlogPostId
     * @param int $mfBlogPostId
     * @return void
     * @throws \Exception
     */
    private function importPostComments(int $mpBlogPostId, int $mfBlogPostId)
    {
        $select = $this->srcConnection->select()->from($this->srcTablePrefix . 'mageplaza_blog_comment')
            ->where('post_id = ?', $mpBlogPostId);
        $mpBlogComments = $this->srcConnection->fetchAll($select);

        $oldCommentsMap = [];

        foreach ($mpBlogComments as $mpBlogComment) {
            if (!$mpBlogComment['content']) {
                continue;
            }

            $commentData = [
                'parent_id' => 0,
                'post_id' => $mfBlogPostId,
                'status' => ($mpBlogComment['status'] == 3) ?
                    \Magefan\Blog\Model\Config\Source\CommentStatus::PENDING :
                    $mpBlogComment['status'],
                'author_type' => \Magefan\Blog\Model\Config\Source\AuthorType::GUEST,
                'author_nickname' => $mpBlogComment['user_name'],
                'author_email' => $mpBlogComment['user_email'],
                'text' => $mpBlogComment['content'],
                'creation_time' => $mpBlogComment['created_at'],
            ];

            $comment = $this->_commentFactory->create($commentData);

            try {
                /* saving */
                $comment->setData($commentData)->save();

                $oldCommentsMap[$mpBlogComment['comment_id']] = $comment;

                $this->_importedCommentsCount++;
            } catch (\Exception $e) {

                $this->_skippedComments[] = $mpBlogComment['comment_id'];
                unset($comment);
            }

            // set parent comments
            foreach ($mpBlogComments as $mpBlogComment) {
                if (isset($oldCommentsMap[$mpBlogComment['comment_id']])) {
                    $parentComment = $oldCommentsMap[$mpBlogComment['reply_id']] ?? null;

                    if ($parentComment) {
                        $comment = $oldCommentsMap[$mpBlogComment['comment_id']];
                        $comment->setParentId($parentComment->getId());

                        $comment->save();
                    }
                }
            }
        }
    }

    /**
     * Get post categories.
     *
     * @param int $mpBlogPostId
     * @return array
     */
    private function getPostCategories(int $mpBlogPostId): array
    {
        $postCategories = [];

        $select = $this->srcConnection->select()
            ->from($this->srcTablePrefix . 'mageplaza_blog_post_category', ['category_id'])
            ->where('post_id = ?', $mpBlogPostId);

        $mpBlogPostCategories = $this->srcConnection->fetchAll($select);

        foreach ($mpBlogPostCategories as $mpBlogPostCategory) {
            $oldId = $mpBlogPostCategory['category_id'];

            if (isset($this->oldCategories[$oldId])) {
                $id = $this->oldCategories[$oldId]->getId();
                $postCategories[$id] = $id;
            }
        }

        return $postCategories;
    }

    /**
     * Get post tags.
     *
     * @param int $mpBlogPostId
     * @return array
     */
    private function getPostTags(int $mpBlogPostId): array
    {
        $postTags = [];
        $t_select = $this->srcConnection->select()
            ->from($this->srcTablePrefix . 'mageplaza_blog_post_tag', ['tag_id'])
            ->where('post_id = ?', $mpBlogPostId);
        $t_result = $this->srcConnection->fetchAll($t_select);

        foreach ($t_result as $t_data) {
            $oldId = $t_data['tag_id'];
            if (isset($this->oldTags[$oldId])) {
                $id = $this->oldTags[$oldId]->getId();
                $postTags[$id] = $id;
            }
        }

        return $postTags;
    }

    /**
     * Get Magefan post status.
     *
     * @param int $mpBlogStatus
     * @return int
     */
    private function getMfPostStatus(int $mpBlogStatus): int
    {
        // possible statuses: const PENDING = '0'; || const APPROVED = '1'; || const DISAPPROVED = '2';
        if ($mpBlogStatus === 1) {
            return MagefanPost::STATUS_ENABLED;
        }

        return MagefanPost::STATUS_DISABLED;
    }
}
