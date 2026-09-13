<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model;

/**
 * Wordpress import model
 */
class Wordpress extends AbstractImport
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_dbConnection = null;

    /**
     * @var string[]
     */
    protected $_requiredFields = ['dbname', 'uname', 'dbhost'];

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
     * Import Category
     *
     * @return void
     * @throws \Exception
     */
    private function importCategory(): void
    {
        $categories = [];
        $this->oldCategories = [];

        $select = $this->srcConnection->select()
            ->from(['t' => $this->srcTablePrefix.'terms'], [
                'old_id' => 'term_id',
                'title' => 'name',
                'identifier' => 'slug'
            ])
            ->joinLeft(
                ['tt' => $this->srcTablePrefix.'term_taxonomy'],
                't.term_id = tt.term_id',
                ['parent_id' => 'parent']
            )
            ->where('tt.taxonomy = ?', 'category')
            ->where('t.slug != ?', 'uncategorized');

        $result = $this->srcConnection->fetchAll($select);

        foreach ($result as $data) {
            /* Prepare category data */
            $data['store_ids'] = [$this->getStoreId()];
            $data['is_active'] = 1;
            $data['include_in_menu'] = 1;
            $data['position'] = 0;
            $data['path'] = 0;
            $data['identifier'] = $this->prepareIdentifier($data['identifier']);

            $category = $this->_categoryFactory->create();
            try {
                /* Initial saving */
                $category->setData($data)->save();
                $this->_importedCategoriesCount++;
                $categories[$category->getId()] = $category;
                $this->oldCategories[$category->getOldId()] = $category;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($category);
                if (!isset($data['title'])) {
                    $data['title'] = 'Undefined';
                }
                $this->_skippedCategories[] = $data['title'];
                $this->_logger->debug('Blog Category Import [' . $data['title'] . ']: '. $e->getMessage());
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
     * Impost Tag
     *
     * @return void
     * @throws \Exception
     */
    private function importTag(): void
    {
        $tags = [];
        $this->oldTags = [];

        $select = $this->srcConnection->select()
            ->from(['t' => $this->srcTablePrefix.'terms'], [
                'old_id' => 'term_id',
                'title' => 'name',
                'identifier' => 'slug'
            ])
            ->joinLeft(
                ['tt' => $this->srcTablePrefix.'term_taxonomy'],
                't.term_id = tt.term_id',
                ['parent_id' => 'parent']
            )
            ->where('tt.taxonomy = ?', 'post_tag')
            ->where('t.slug != ?', 'uncategorized');

        $result = $this->srcConnection->fetchAll($select);
        foreach ($result as $data) {
            /* Prepare tag data */
            if (isset($data['title']) && isset($data['title'][0]) && $data['title'][0] == '?') {
                /* fix for ???? titles */
                $data['title'] = $data['identifier'];
            }

            $data['identifier'] = $this->prepareIdentifier($data['identifier']);

            $tag = $this->_tagFactory->create();
            try {
                /* Initial saving */
                $tag->setData($data)->save();
                $this->_importedTagsCount++;
                $tags[$tag->getId()] = $tag;
                $this->oldTags[$tag->getOldId()] = $tag;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($tag);
                if (!isset($data['title'])) {
                    $data['title'] = 'Undefined';
                }
                $this->_skippedTags[] = $data['title'];
                $this->_logger->debug('Blog Tag Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }
    }

    /**
     * Import Author
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

        $this->oldAuthors = [];
        $select = $this->srcConnection->select()
            ->from(['p' => $this->srcTablePrefix . 'posts'], [
                'post_author'
            ])
            ->joinLeft(
                ['u' => $this->srcTablePrefix . 'users'],
                'p.post_author = u.ID',
                [
                    'identifier' => 'user_nicename',
                    'email' => 'user_email',
                    'display_name',
                ]
            )
            ->where('p.post_type = ?', 'post')
            ->group('p.post_author');

        $result = $this->srcConnection->fetchAll($select);

        foreach ($result as $data) {
            /* Prepare author data */
            if (!empty($data['display_name'])) {
                $data['display_name'] = explode(' ', $data['display_name'], 2);
                $data['firstname'] = !empty($data['display_name'][0]) ? $data['display_name'][0] : '';
                $data['lastname'] = !empty($data['display_name'][1]) ? $data['display_name'][1] : '';
            }

            $data['identifier'] = $this->prepareIdentifier($data['identifier']);
            $author = $this->_authorFactory->create();
            try {
                /* Initial saving */
                $author->setData($data)->save();
                $this->_importedAuthorsCount++;
                $this->oldAuthors[$data['post_author']] = $author;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($author);
                $this->_logger->debug('Blog Author Import [' . $data['identifier'] . ']: ' . $e->getMessage());
            }
        }
    }

    /**
     * Import Post
     *
     * @return void
     * @throws \Exception
     */
    private function importPost():void
    {
        $select = $this->srcConnection->select()
            ->from($this->srcTablePrefix.'posts')
            ->where('post_type = ?', 'post')
            ->where('post_status NOT LIKE ?', 'auto-draft');

        $result = $this->srcConnection->fetchAll($select);

        foreach ($result as $data) {
            $objectId = (int)$data['ID'];

            $this->preparePostData($objectId, $data);

            $post = $this->_postFactory->create();

            try {
                /* Post saving */
                $post->setData($data)->save();
                $this->_importedPostsCount++;

                $this->importPostComments($objectId, (int)$post->getId());
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if (!isset($data['title'])) {
                    $data['title'] = 'Undefined';
                }
                $this->_skippedPosts[] = $data['title'];
                $this->_logger->debug('Blog Post Import [' . $data['title'] . ']: '. $e->getMessage());
            }

            unset($post);
        }
    }

    /**
     * Get Post Categories
     *
     * @param int $objectId
     * @return array
     */
    private function getPostCategories(int $objectId): array
    {
        $postCategories = [];

        $select = $this->srcConnection->select()
            ->from(['tr' => $this->srcTablePrefix . 'term_relationships'], ['term_id' => 'tt.term_id'])
            ->join(
                ['tt' => $this->srcTablePrefix . 'term_taxonomy'],
                'tr.term_taxonomy_id = tt.term_taxonomy_id',
                []
            )
            ->where('tr.object_id = ?', $objectId)
            ->where('tt.taxonomy = ?', 'category');

        $result = $this->srcConnection->fetchAll($select);

        foreach ($result as $data) {
            $oldTermId = $data['term_id'];
            if (isset($this->oldCategories[$oldTermId])) {
                $postCategories[] = $this->oldCategories[$oldTermId]->getId();
            }
        }

        return $postCategories;
    }

    /**
     * Get Post Tags
     *
     * @param int $objectId
     * @return array
     */
    private function getPostTags(int $objectId): array
    {
        $postTags = [];

        $select = $this->srcConnection->select()
            ->from(['tr' => $this->srcTablePrefix . 'term_relationships'], ['term_id' => 'tt.term_id'])
            ->join(
                ['tt' => $this->srcTablePrefix . 'term_taxonomy'],
                'tr.term_taxonomy_id = tt.term_taxonomy_id',
                []
            )
            ->where('tr.object_id = ?', $objectId)
            ->where('tt.taxonomy = ?', 'post_tag');

        $result = $this->srcConnection->fetchAll($select);

        foreach ($result as $data) {
            $oldTermId = $data['term_id'];

            if (isset($this->oldTags[$oldTermId])) {
                $postTags[] = $this->oldTags[$oldTermId]->getId();
            }
        }

        return $postTags;
    }

    /**
     * Get Featured Image
     *
     * @param int $objectId
     * @return string
     */
	// phpcs:disable Generic.Metrics.NestingLevel
    private function getFeaturedImg(int $objectId): string
    {
        $featuredImg = '';

        $select = $this->srcConnection->select()
            ->from(['p1' => $this->srcTablePrefix . 'posts'], ['featured_img' => 'wm2.meta_value'])
            ->join(
                ['wm1' => $this->srcTablePrefix . 'postmeta'],
                'wm1.post_id = p1.id AND wm1.meta_value IS NOT NULL AND wm1.meta_key = "_thumbnail_id"',
                []
            )
            ->join(
                ['wm2' => $this->srcTablePrefix . 'postmeta'],
                'wm1.meta_value = wm2.post_id AND wm2.meta_key = "_wp_attached_file" AND wm2.meta_value IS NOT NULL',
                []
            )
            ->where('p1.ID = ?', $objectId)
            ->where('p1.post_type = ?', 'post')
            ->order('p1.post_date DESC');

        $result = $this->srcConnection->fetchAll($select);

        foreach ($result as $data) {
            if ($data['featured_img']) {
                $featuredImg = \Magefan\Blog\Model\Post::BASE_MEDIA_PATH . '/' . $data['featured_img'];
                break;
            }
        }

        if (empty($featuredImg)) {
            $select = $this->srcConnection->select()
                ->from(['p1' => $this->srcTablePrefix . 'posts'], ['featured_img' => 'wm1.meta_value'])
                ->join(
                    ['wm1' => $this->srcTablePrefix . 'postmeta'],
                    'wm1.post_id = p1.id AND wm1.meta_value IS NOT NULL AND wm1.meta_key = "dfiFeatured"',
                    []
                )
                ->where('p1.ID = ?', $objectId)
                ->where('p1.post_type = ?', 'post')
                ->order('p1.post_date DESC');

            $result = $this->srcConnection->fetchAll($select);

            foreach ($result as $data) {
                if ($data['featured_img']) {
                    $serializeInterface = \Magento\Framework\App\ObjectManager::getInstance()
                        ->create(\Magento\Framework\Serialize\SerializerInterface::class);
                    $tmpArr = $serializeInterface->unserialize($data['featured_img']);
                    if (is_array($tmpArr)) {
                        foreach ($tmpArr as $item) {
                            $item = trim($item);
                            $item = explode(',', $item);
                            $item = $item[count($item) - 1];
                            if ($item) {
                                $featuredImg = \Magefan\Blog\Model\Post::BASE_MEDIA_PATH . '/' . $item;
                                break 2;
                            }

                        }
                    }
                }
            }
        }

        return $featuredImg;
    }

    /**
     * Get Post Meta Data
     *
     * @param int $objectId
     * @return array
     */
    private function getMetaData(int $objectId): array
    {
        $result = [
            'short_content' => '',
            'meta_title' => '',
            'meta_description' => ''
        ];

        $select = $this->srcConnection->select()
            ->from($this->srcTablePrefix . 'postmeta')
            ->where('post_id = ?', $objectId);

        $metaResult = $this->srcConnection->fetchAll($select);

        foreach ($metaResult as $metaData) {

            $metaValue = trim(isset($metaData['meta_value']) ? $metaData['meta_value'] : '');
            if (!$metaValue) {
                continue;
            }

            switch ($metaData['meta_key']) {
                case 'wpcf-meta-description':
                    $result['short_content'] = $metaValue;
                    break;
                case '_yoast_wpseo_title':
                    $result['meta_title'] = $metaValue;
                    break;
                case '_yoast_wpseo_metadesc':
                    $result['meta_description'] = $metaValue;
                    break;
            }
        }

        return $result;
    }

    /**
     * Prepare Post Data
     *
     * @param int $objectId
     * @param array $data
     * @return void
     */
    private function preparePostData(int $objectId, array &$data): void
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $contentHelper = $objectManager->get(\Magefan\BlogImport\Model\WordpressContentHelper::class);

        $metaData = $this->getMetaData($objectId);

        $creationTime = strtotime((string)$data['post_date_gmt']);

        $data = [
            'store_ids' => [$this->getStoreId()],
            'title' => $data['post_title'],
            'meta_title' => $metaData['meta_title'],
            'meta_description' => $metaData['meta_description'],
            'meta_keywords' => '',
            'identifier' => $this->prepareIdentifier($data['post_name']),
            'content_heading' => '',
            'content' => $contentHelper->prepare((string)$data['post_content']),
            'short_content' => $metaData['short_content'],
            'featured_img' => $this->getFeaturedImg($objectId),
            'creation_time' => $creationTime,
            'update_time' => strtotime((string)$data['post_modified_gmt']),
            'publish_time' => $creationTime,
            'is_active' => (int)($data['post_status'] == 'publish'),
            'categories' => $this->getPostCategories($objectId),
            'tags' => $this->getPostTags($objectId),
            'author_id' => (isset($data['post_author']) && isset($this->oldAuthors[$data['post_author']]))
                ? $this->oldAuthors[$data['post_author']]->getId()
                : null,
        ];
    }

    /**
     * Import Post Comments
     *
     * @param int $objectId
     * @param int $mfBlogPostId
     * @return void
     */
    private function importPostComments(int $objectId, int $mfBlogPostId): void
    {
        $select = $this->srcConnection->select()
            ->from($this->srcTablePrefix . 'comments')
            ->where('comment_approved = ?', 1)
            ->where('comment_post_ID = ?', $objectId);

        $resultComments = $this->srcConnection->fetchAll($select);

        $commentParents = [];

        foreach ($resultComments as $comments) {
            $commentParentId = 0;

            if (!($comments['comment_parent'] == 0) && isset($commentParents[$comments["comment_parent"]])) {
                $commentParentId = $commentParents[$comments["comment_parent"]];
            }

            $commentData = [
                'parent_id' => $commentParentId,
                'post_id' => $mfBlogPostId,
                'status' => \Magefan\Blog\Model\Config\Source\CommentStatus::APPROVED,
                'author_type' => \Magefan\Blog\Model\Config\Source\AuthorType::GUEST,
                'author_nickname' => $comments['comment_author'],
                'author_email' => $comments['comment_author_email'],
                'text' => $comments['comment_content'],
                'creation_time' => $comments['comment_date'],
            ];

            if (!$commentData['text']) {
                continue;
            }

            $comment = $this->_commentFactory->create($commentData);

            try {
                /* Initial saving */
                $comment->setData($commentData)->save();
                $this->_importedCommentsCount++;
                $commentParents[$comments["comment_ID"]] = $comment->getCommentId();
            } catch (\Exception $e) {
                if (!isset($commentData['title'])) {
                    $commentData['title'] = 'Undefined';
                }
                $this->_skippedComments[] = $commentData['title'];
                unset($comment);
            }
        }
    }
}
