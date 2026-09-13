<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogImport\Model\WordpressV2Import;

use Magefan\BlogImport\Model\WordpressV2Import\Import;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\ResourceConnection;
use Magefan\Blog\Api\PostRepositoryInterface;
use Magefan\BlogImport\Model\WordpressV2Import\OldIdsToNewIdsRelationManager;
use Psr\Log\LoggerInterface;
use Magefan\BlogImport\Model\WordpressContentHelper;

class Post
{
    /**
     * @var PostRepositoryInterface
     */
    private $postRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var DownloadFilesFromContent
     */
    private $downloadFilesFromContent;

    /**
     * @var OldIdsToNewIdsRelationManager
     */
    private $oldIdsToNewIdsRelationManager;

    /**
     * @var WordpressContentHelper
     */
    private $wordpressContentHelper;

    /**
     * @var string[]
     */
    private $allowedColumns = [
        'post_id', 'title', 'meta_title', 'meta_keywords', 'meta_description', 'identifier',
        'og_title', 'og_description', 'og_img', 'og_type', 'content_heading', 'content',
        'creation_time', 'update_time', 'publish_time', 'end_time', 'is_active',
        'include_in_recent', 'position', 'featured_img', 'featured_img_alt',
        'featured_list_img', 'featured_list_img_alt', 'user_id', 'author_id', 'page_layout',
        'layout_update_xml', 'custom_theme', 'custom_layout', 'custom_layout_update_xml',
        'custom_theme_from', 'custom_theme_to', 'media_gallery', 'secret', 'views_count',
        'is_recent_posts_skip', 'short_content', 'comments_count', 'exclude_xml_sitemap',
        'structure_data_type', 'meta_robots', 'custom_css', 'categories', 'tags'
    ];

    /**
     * @var array
     */
    private $wpPostIdToMfBlogAuthorIdMap = [];

    /**
     * @param PostRepositoryInterface $postRepository
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param DownloadFilesFromContent $downloadFilesFromContent
     * @param OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager
     * @param WordpressContentHelper $wordpressContentHelper
     */
    public function __construct(
        PostRepositoryInterface $postRepository,
        LoggerInterface $logger,
        DateTime $dateTime,
        DownloadFilesFromContent $downloadFilesFromContent,
        OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager,
        WordpressContentHelper $wordpressContentHelper
    ) {
        $this->postRepository = $postRepository;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->downloadFilesFromContent = $downloadFilesFromContent;
        $this->oldIdsToNewIdsRelationManager = $oldIdsToNewIdsRelationManager;
        $this->wordpressContentHelper = $wordpressContentHelper;
    }

    /**
     * Process post import
     *
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $this->wpPostIdToMfBlogAuthorIdMap = [];

        foreach ($data as $postData) {
            $this->createPost($postData);
        }

        $this->oldIdsToNewIdsRelationManager->updateMap($this->wpPostIdToMfBlogAuthorIdMap, Import::POST);

        return $this->wpPostIdToMfBlogAuthorIdMap;
    }

    /**
     * Create post
     *
     * @param array $postData
     * @return void
     */
    private function createPost(array $postData): void
    {
        try {
            if (empty($postData['title'])) {
                return;
            }

            $post = $this->postRepository->getFactory()->create();
            $post->addData($this->prepareData($postData));
            $this->postRepository->save($post);

            $wpPostId = $postData['old_id'];
            $mfPostId = $post->getId();

            $this->wpPostIdToMfBlogAuthorIdMap[$wpPostId] = $mfPostId;
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Prepare post data
     *
     * @param array $postData
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function prepareData(array $postData): array
    {
        $postData = array_filter($postData, function ($key) {
            return in_array($key, $this->allowedColumns, true);
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($postData['is_active'])) {
            // use unset since default value is 1; avoid issue with PostPublishPermissionPlugin
            unset($postData['is_active']);
        }

        if (isset($postData['publish_time'])) {
            $postData['publish_time'] = $this->dateTime->date(null, $postData['publish_time']);
        }

        if (!empty($postData['content'])) {
            $postData['content'] = $this->wordpressContentHelper->prepare((string)$postData['content']);
        }

        if (!empty($postData['featured_img'])) {
            $postData['featured_img'] = $this->downloadFilesFromContent->downloadFileByUrl(
                $postData['featured_img'],
                '/images/'
            );
        }

        $postData['categories'] = $this->oldIdsToNewIdsRelationManager
            ->getMfBlogIdsByWpIds((array)$postData['categories'], Import::CATEGORY);
        $postData['tags'] = $this->oldIdsToNewIdsRelationManager
            ->getMfBlogIdsByWpIds((array)$postData['tags'], Import::TAG);
        $mfAuthorId = $this->oldIdsToNewIdsRelationManager
            ->getMfBlogIdsByWpIds((array)$postData['author_id'], Import::AUTHOR);
        $postData['author_id'] = $mfAuthorId[0] ?? null;

        return $postData;
    }
}
