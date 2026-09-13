<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogImport\Model\WordpressV2Import;

use Magefan\BlogImport\Model\WordpressV2Import\OldIdsToNewIdsRelationManager;
use Magefan\Blog\Api\TagRepositoryInterface;
use Psr\Log\LoggerInterface;

class Tag
{
    /**
     * @var OldIdsToNewIdsRelationManager
     */
    private $oldIdsToNewIdsRelationManager;

    /**
     * @var TagRepositoryInterface
     */
    private $tagRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $wpTagIdToMfBlogTagIdMap = [];

    /**
     * @param OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager
     * @param TagRepositoryInterface $tagRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager,
        TagRepositoryInterface $tagRepository,
        LoggerInterface $logger
    ) {
        $this->oldIdsToNewIdsRelationManager = $oldIdsToNewIdsRelationManager;
        $this->tagRepository = $tagRepository;
        $this->logger = $logger;
    }

    /**
     * Process tag import
     *
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $this->wpTagIdToMfBlogTagIdMap = [];

        foreach ($data as $tagData) {
            $this->createTag($tagData);
        }

        $this->oldIdsToNewIdsRelationManager->updateMap($this->wpTagIdToMfBlogTagIdMap, Import::TAG);

        return $this->wpTagIdToMfBlogTagIdMap;
    }

    /**
     * Create tag
     *
     * @param array $tagData
     * @return void
     */
    private function createTag(array $tagData): void
    {
        $preparedData = [
            'title' => $tagData['title']
        ];

        try {
            $tag = $this->tagRepository->getFactory()->create();
            $tag->setData($preparedData);
            $this->tagRepository->save($tag);

            $wpTagId = $tagData['old_id'];
            $mfTagId = $tag->getId();
            $this->wpTagIdToMfBlogTagIdMap[$wpTagId] = $mfTagId;
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
