<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model;

/**
 * Joomla import model
 */
class Joomla extends \Magefan\BlogImport\Model\AbstractImport
{
    /**
     * @var string[]
     */
    protected $_requiredFields = ['dbname', 'uname', 'pwd', 'dbhost', 'prefix'];

    /**
     * Executes the import process for blog categories and posts, handling data transformation, saving, and reindexing.
     *
     * @return void
     * @throws \Exception
     */
    public function execute(): void
    {
        $_pref = $this->getPrefix();
        $categories = [];
        $oldCategories = [];
        $connection = $this->getDbConnection();
        /* Import categories */
        $select = $connection->select()
            ->from(['c' => $_pref . 'categories'], [
                'old_id' => 'id',
                'content' => 'description',
                'parent_id' => 'parent_id',
                'position' => 'lft',
                'is_active' => 'published',
                'meta_description' => 'metadesc',
                'meta_keywords' => 'metakey',
                'title' => 'title',
                'identifier' => 'alias'
            ])
            ->where('extension = ?', 'com_content');
        $result = $connection->fetchAll($select);
        foreach ($result as $data) {
            /* Prepare category data */
            foreach (['title', 'identifier','meta_description','meta_keywords','content'] as $key) {
                $data[$key] = mb_convert_encoding($data[$key], "UTF-8", "ISO-8859-1");
            }

            $data['content'] = $this->parseContent((string)$data['content']);

            $data['store_ids'] = [$this->getStoreId()];
            $data['is_active'] = (int)($data['is_active'] == 1);
            $data['identifier'] = trim(strtolower($data['identifier']));
            $data['include_in_menu'] = 1;
            if (strlen($data['identifier']) == 1) {
                $data['identifier'] .= $data['identifier'];
            }

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

        /* Reindexing parent categories */
        foreach ($categories as $ct) {
            if ($oldParentId = $ct->getData('parent_id')) {
                if (isset($oldCategories[$oldParentId])) {
                    $ct->setPath(
                        $parentId = $oldCategories[$oldParentId]->getId()
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

        /* Import posts */
        $select = $connection->select()
            ->from(['c' => $_pref . 'content'], [
                'meta_keywords' => 'metakey',
                'meta_description' => 'metadesc',
                'creation_time' => 'created',
                'update_time' => 'modified',
                'publish_time' => 'publish_up',
                'category_id' => 'catid',
                'post_title' => 'title',
                'is_active' => 'access',
                'identifier' => 'alias',
                'short_content' => 'introtext',
                'post_content' => 'fulltext'
            ]);
        $result = $connection->fetchAll($select);
        foreach ($result as $data) {

            if (isset($oldCategories[$data['category_id']])) {
                $postCategories = [
                    $oldCategories[$data['category_id']]->getId()
                ];
            } else {
                $postCategories = [];
            }

            $data['featured_img'] = '';

            /* Prepare post data */
            $postFields = [
                'post_title', 'meta_keywords',
                'meta_description', 'identifier',
                'post_content', 'identifier'
            ];
            foreach ($postFields as $key) {
                $data[$key] = mb_convert_encoding($data[$key], "UTF-8", "ISO-8859-1");
            }

            $creationTime = strtotime($data['creation_time']);

            $content = $this->parseContent((string)$data['post_content']);
            $shortContent = $this->parseContent((string)$data['short_content']);

            $data = [
                'store_ids' => [$this->getStoreId()],
                'title' => $data['post_title'],
                'meta_keywords' => $data['meta_keywords'],
                'meta_description' => $data['meta_description'],
                'identifier' => $data['identifier'],
                'content_heading' => '',
                'content' => $content,
                'short_content' => $shortContent,
                'creation_time' => $creationTime,
                'update_time' => strtotime($data['update_time']),
                'publish_time' => $creationTime,
                'is_active' => (int)($data['is_active'] == 1),
                'categories' => $postCategories,
            ];
            $data['identifier'] = trim(strtolower($data['identifier']));
            if (strlen($data['identifier']) == 1) {
                $data['identifier'] .= $data['identifier'];
            }
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

    /**
     * Parse content for Joomla import
     *
     * @param string $content
     * @return array|string|string[]|null
     */
    protected function parseContent(string $content)
    {
        $content = str_replace('<!--more-->', '<!-- pagebreak -->', $content);

        $content = preg_replace(
            '/src="images\/(.*)"/Ui',
            'src="{{media url=\'wysiwyg/images/$1\'}}"',
            $content
        );

        $content = preg_replace(
            '/src=\'images\/(.*)\'/Ui',
            'src="{{media url=\'wysiwyg/images/$1\'}}"',
            $content
        );

        $content = preg_replace(
            '/"((shop)(.*)|(\s|"|\')|(\/[\d\w_\-\.]*))\/(.*)(\s|"|\')/Ui',
            '$4"{{store url=$6$8}}"$9',
            $content
        );

        //$content = str_replace('<h1 class="bottomline">' . $data['post_title'] . '</h1>', '', $content);

        return $content;
    }
}
