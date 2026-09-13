<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model;

use Magefan\BlogImport\Model\AbstractImport;

/**
 * Blog import model
 */
class Amasty1 extends AbstractImport
{
    /**
     * @var string[]
     */
    protected $_requiredFields = ['dbname', 'uname', 'dbhost'];

    /**
     * Execute import.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(): void
    {
        $connection = $this->getDbConnection();
        $_pref = $this->getPrefix();

        $select = $connection->select()
            ->from($_pref . 'mp_blog_posts')->limit(1);
        try {
            $connection->fetchAll($select);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Amasty Blog Extension for Magento 1 not detected.')
            );
        }

        $storeIds = array_keys($this->_storeManager->getStores(true));

        $categories = [];
        $oldCategories = [];

        /* Import categories */
        $select = $connection->select()
            ->from(['t' => $_pref . 'mp_blog_categories'], [
                'old_id' => 'category_id',
                'title' => 'name',
                'identifier' => 'url_key',
                'is_active' => 'status',
                'position' => 'sort_order',
                'meta_title' => 'meta_title',
                'meta_keywords' => 'meta_tags',
                'meta_description' => 'meta_description'
            ]);

        $result = $connection->fetchAll($select);

        foreach ($result as $data) {
            /* Prepare category data */

            /* Find store ids */
            $data['store_ids'] = [];

            $s_select = $connection->select()
                ->from($_pref . 'mp_blog_categories_store', ['store_id'])
                ->where('category_id = ?', (int)$data['old_id']);

            $s_result = $connection->fetchAll($s_select);

            foreach ($s_result as $s_data) {
                $data['store_ids'][] = $s_data['store_id'];
            }

            foreach ($data['store_ids'] as $key => $id) {
                if (!in_array($id, $storeIds)) {
                    unset($data['store_ids'][$key]);
                }
            }

            if (empty($data['store_ids']) || in_array(0, $data['store_ids'])) {
                $data['store_ids'] = 0;
            }

            $data['path'] = 0;
            $data['include_in_menu'] = 1;

            $category = $this->_categoryFactory->create();
            try {
                /* Initial saving */
                $category->setData($data)->save();
                $this->_importedCategoriesCount++;
                $categories[$category->getId()] = $category;
                $oldCategories[$category->getOldId()] = $category;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($category);
                $this->_skippedCategories[] = $data['title'];
                $this->_logger->debug('Blog Category Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }

        /* Import tags */
        $tags = [];
        $oldTags = [];
        $existingTags = [];

        $select = $connection->select()
            ->from(['t' => $_pref . 'mp_blog_tags'], [
                'old_id' => 'tag_id',
                'title' => 'name',
                'identifier' => 'url_key',
                'meta_title' => 'meta_title',
                'meta_keywords' => 'meta_tags',
                'meta_description' => 'meta_description',
                'is_active' => new \Zend_Db_Expr('1')
            ]);

        $result = $connection->fetchAll($select);

        foreach ($result as $data) {

            if (!$data['title']) {
                continue;
            }
            $data['title'] = trim($data['title']);
            if (is_numeric($data['title'])) {
                $data['title'] = 't' . $data['title'];
            }

            $data['store_ids'] = [0];

            try {
                /* Initial saving */
                if (!isset($existingTags[$data['title']])) {
                    $tag = $this->_tagFactory->create();
                    $tag->setData($data);

                    $currentTag = $tag->getCollection()
                        ->addFieldToFilter('title', $tag->getTitle())
                        ->setPageSize(1)
                        ->getFirstItem();
                    if ($currentTag->getId()) {
                        $tag = $currentTag;
                    } else {
                        $tag->save();
                    }
                    $this->_importedTagsCount++;
                    $tags[$tag->getId()] = $tag;
                    $oldTags[$tag->getOldId()] = $tag;
                    $existingTags[$tag->getTitle()] = $tag;
                } else {
                    $tag = $existingTags[$data['title']];
                    $oldTags[$data['old_id']] = $tag;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedTags[] = $data['title'];
                $this->_logger->debug('Blog Tag Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }

        /* Import authors */
        $authors = [];
        $oldAuthors = [];

        $select = $connection->select()
            ->from(['t' => $_pref . 'mp_blog_authors'], [
                'old_id' => 'author_id',
                'name' => 'name',
                'googleplus_page_url' => 'google_profile',
                'facebook_page_url' => 'facebook_profile',
                'twitter_page_url' => 'twitter_profile'
            ]);

        $result = $connection->fetchAll($select);

        foreach ($result as $data) {

            if (!$data['name'] || !$data['old_id']) {
                continue;
            }

            $name = explode(' ', $data['name']);

            $data['firstname'] = trim($name[0]);
            unset($name[0]);

            if (count($name)) {
                $data['lastname'] = implode(' ', $name);
            } else {
                $data['lastname'] = '';
            }

            try {
                $author = $this->_authorFactory->create();
                $author->setData($data);
                $author->save();
                $this->_importedAuthorsCount++;
                $authors[$author->getId()] = $author;
                $oldAuthors[$author->getOldId()] = $author;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedAuthors[] = $data['firstname'] . ' ' . $data['lastname'];
                $this->_logger->debug(
                    'Blog Author Import ['
                    . $data['firstname']
                    . ' ' . $data['lastname'] . ']: '
                    . $e->getMessage()
                );
            }
        }

        /* Import posts */

        $select = $connection->select()
            ->from($_pref . 'mp_blog_posts', ['*']);
        $result = $connection->fetchAll($select);

        foreach ($result as $data) {

            /* Find post categories*/
            $postCategories = [];

            $c_select = $connection->select()
                ->from($_pref . 'mp_blog_posts_category', ['category_id'])
                ->where('post_id = ?', $data['post_id']);

            $c_result = $connection->fetchAll($c_select);

            foreach ($c_result as $c_data) {
                $oldId = $c_data['category_id'];
                if (isset($oldCategories[$oldId])) {
                    $id = $oldCategories[$oldId]->getId();
                    $postCategories[$id] = $id;
                }
            }
            /* Find post tags*/
            $postTags = [];

            $c_select = $connection->select()
                ->from($_pref . 'mp_blog_posts_tag', ['tag_id'])
                ->where('post_id = ?', $data['post_id']);

            $c_result = $connection->fetchAll($c_select);

            foreach ($c_result as $c_data) {
                $oldId = $c_data['tag_id'];
                if (isset($oldTags[$oldId])) {
                    $id = $oldTags[$oldId]->getId();
                    $postTags[$id] = $id;
                }
            }

            /* Find store ids */
            $data['store_ids'] = [];

            $s_select = $connection->select()
                    ->from($_pref . 'mp_blog_posts_store', ['store_id'])
                    ->where('post_id = ?', (int)$data['post_id']);

            $s_result = $connection->fetchAll($s_select);

            foreach ($s_result as $s_data) {
                $data['store_ids'][] = $s_data['store_id'];
            }

            foreach ($data['store_ids'] as $key => $id) {
                if (!in_array($id, $storeIds)) {
                    unset($data['store_ids'][$key]);
                }
            }

            if (empty($data['store_ids']) || in_array(0, $data['store_ids'])) {
                $data['store_ids'] = [0];
            }

            /* Find post author */
            $postAuthorId = null;

            $authors = $this->_authorFactory->create()->getCollection()->setPageSize(1);
            $authors->getSelect()->where('CONCAT(firstname, " ", lastname) LIKE ?', '%' . $data['posted_by'] . '%');
            $author = $authors->getFirstItem();
            if ($author->getId()) {
                $postAuthorId = $author->getId();
            } else {
                $name = explode(' ', $data['posted_by']);

                $authorData['firstname'] = trim($name[0]);
                unset($name[0]);

                if (count($name)) {
                    $authorData['lastname'] = implode(' ', $name);
                } else {
                    $authorData['lastname'] = '';
                }

                try {
                    $author = $this->_authorFactory->create();
                    $author->setData($authorData);
                    $author->save();
                    $postAuthorId  = $author->getId();
                    /*
                    $this->_importedAuthorsCount++;
                    $authors[$author->getId()] = $author;
                    $oldAuthors[$author->getOldId()] = $author;
                    */
				// phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    /*
                    $this->_skippedAuthors[] = $data['firstname'] . ' ' . $data['lastname'];
                    $this->_logger->debug(
                    'Blog Author Import [' . $data['firstname'] . ' ' . $data['lastname'] . ']: '. $e->getMessage());
                    */
                }
            }

            /* Find post author */
            /*
            $oldAuthorId = $data['author_id'];
            if (isset($oldAuthors[$oldAuthorId])) {
                $data['author_id'] = $oldAuthors[$oldAuthorId]->getId();
            } else {
                $data['author_id'] = null;
            }
            */

            if ($data['status'] > 0) {
                $data['status'] = 1;
            }

            /* Prepare post data */
            $data = [
                'old_id' => $data['post_id'],
                'store_ids' => $data['store_ids'],
                'title' => $data['title'],
                'meta_title' => $data['meta_title'],
                'meta_keywords' => $data['meta_tags'],
                'meta_description' => $data['meta_description'],
                'identifier' => $data['url_key'],
                'content_heading' => '',
                'content' => $data['full_content'],
                'short_content' => $data['short_content'],
                'creation_time' => $data['created_at'],
                'update_time' => $data['updated_at'],
                'publish_time' => $data['published_at'],
                'is_active' => (int)($data['status'] == 1),
                'categories' => $postCategories,
                'author_id' => $postAuthorId ,
                'tags' => $postTags,
                'featured_img' => trim((string)$data['post_thumbnail'], '/'),
                'featured_list_img' => trim((string)$data['list_thumbnail'], '/'),
            ];

            $post = $this->_postFactory->create();
            try {
                /* Post saving */
                $post->setData($data)->save();

                $this->_importedPostsCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedPosts[] = $data['title'];
                $this->_logger->debug('Blog Post Import [' . $data['title'] . ']: '. $e->getMessage());
            }

            unset($post);
        }
        /* end */
    }
}
