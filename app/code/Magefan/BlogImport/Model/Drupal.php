<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Model;

/**
 * Drupal import model
 * Copy image files
 * /sites/default/files/styles/blog_landing/public/ -> /pub/media/magefan_blog/
 * /sites/default/files/inline-images/ -> /pub/media/wysiwyg/inline-images/
 */
class Drupal extends \Magefan\BlogImport\Model\AbstractImport
{
    /**
     * @var string[]
     */
    protected $_requiredFields = ['dbname', 'uname', 'pwd', 'dbhost'];

    /**
     * Execute import.
     *
     * @return void
     * @throws \Exception
     */
    public function execute(): void
    {
        $connection = $this->getDbConnection();
        $_pref = $this->getPrefix();

        $select = $connection->select()->from($_pref.'node_field_data')->limit(1);
        try {
            $connection->fetchAll($select);
        } catch (\Exception $e) {
            throw new \LocalizedException(__('Drupal database tables not detected.'), 1);
        }

        /* Detect which URL alias table exists:
         * Drupal 8.8+ / 9 / 10 uses "path_alias" (column: path)
         * Drupal 7 / 8 < 8.8  uses "url_alias"  (column: source)
         */
        $usePathAlias = false;
        try {
            $testSelect = $connection->select()->from($_pref . 'path_alias')->limit(1);
            $connection->fetchAll($testSelect);
            $usePathAlias = true;
        } catch (\Exception $e) {
            $usePathAlias = false;
        }

        if ($usePathAlias) {
            $aliasTable  = $_pref . 'path_alias';
            $aliasColumn = 'path';
        } else {
            $aliasTable  = $_pref . 'url_alias';
            $aliasColumn = 'source';
        }

        /* Import posts */
        $donePosts = [];
        $select = $connection->select()
            ->from(['main' => $_pref . 'node_field_data'], [
                'old_id' => 'nid',
                'title',
                'status',
                'created',
                'changed',
            ])
            /* ->joinLeft(
                 ['meta' => $_pref . 'node__field_meta_tags'],
                 'main.nid = meta.entity_id',
                 ['meta_value' => 'field_meta_tags_value']
             )*/
            ->joinLeft(
                ['body' => $_pref . 'node__body'],
                'main.nid = body.entity_id',
                ['content' => 'body_value']
            )
            ->joinLeft(
                ['path_alias' => $aliasTable],
                'path_alias.' . $aliasColumn . ' = CONCAT("/node/", main.nid)',
                ['url_key' => 'alias']
            )
            ->joinLeft(
                ['fu' => $_pref . 'file_usage'],
                'fu.id = main.nid AND fu.type = "node" AND fu.module = "file"',
                []
            )
            ->joinLeft(
                ['fm' => $_pref . 'file_managed'],
                'fm.fid = fu.fid',
                ['featured_img' => 'uri']
            )
            ->where('main.type = ?', 'article');

        $result = $connection->fetchAll($select);
        foreach ($result as $data) {

            if (isset($donePosts[$data['old_id']])) {
                continue;
            }
            $donePosts[$data['old_id']] = $data['old_id'];

            /* Find post store */
            $data['store_ids'] = [$this->getStoreId()];

            /* Find post author */
            $postAuthorId = null;
            try {
                $serializeInterface = \Magento\Framework\App\ObjectManager::getInstance()
                    ->create(\Magento\Framework\Serialize\SerializerInterface::class);
                $metaData = $serializeInterface->unserialize($data['meta_value']);
            } catch (\Exception $e) {
                $metaData = [];
            }

            if ($data['status'] > 0) {
                $data['status'] = 1;
            }

            /* Prepare post data */
            $data = [
                'old_id' => $data['old_id'],
                'store_ids' => $data['store_ids'],
                'title' => $data['title'],

                'meta_title' => isset($metaData['title']) ? $metaData['title'] : '',
                //'meta_keywords' => isset($metaData['title']) ? $metaData['title'] : '',
                'meta_description' => isset($metaData['description']) ? $metaData['description'] : '',

                'og_title' => isset($metaData['og_title']) ? $metaData['og_title'] : '',
                'og_description' => isset($metaData['og_description']) ? $metaData['og_description'] : '',

                'identifier' => str_replace('/blog/', '', $data['url_key']),
                'content_heading' => '',
                'content' => $this->parseContent((string)$data['content']),
                //'short_content' => $data['short_content'],
                'creation_time' => $data['created'],
                'update_time' => $data['changed'],
                'publish_time' => $data['created'],
                'is_active' => (int)($data['status'] == 1),
                //'categories' => $postCategories,
                //'author_id' => $postAuthorId ,
                //'tags' => $postTags,
                'featured_img' => str_replace('public://', 'magefan_blog/', (string)$data['featured_img']),
                //'featured_list_img' => trim((string)$data['list_thumbnail'], '/'),
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

    /**
     * Parse content.
     *
     * @param string $content
     * @return array|string|string[]|null
     */
    protected function parseContent(string $content)
    {
        $content = str_replace('<!--more-->', '<!-- pagebreak -->', $content);

        $content = preg_replace(
            '/src="\/sites\/default\/files\/inline-images\/(.*)"/Ui',
            'src="{{media url=\'wysiwyg/inline-images/$1\'}}"',
            $content
        );

        $content = preg_replace(
            '/src=\'\/sites\/default\/files\/inline-images\/(.*)\'/Ui',
            'src="{{media url=\'wysiwyg/inline-images/$1\'}}"',
            $content
        );

        return $content;
    }
}
