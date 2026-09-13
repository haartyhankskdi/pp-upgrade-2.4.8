<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogImport\Model\WordpressV2Import;

use Magefan\BlogImport\Model\WordpressV2Import\OldIdsToNewIdsRelationManager;
use Magefan\Blog\Api\CommentRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class Comment
{
    /**
     * @var OldIdsToNewIdsRelationManager
     */
    private $oldIdsToNewIdsRelationManager;

    /**
     * @var CommentRepositoryInterface
     */
    private $commentRepository;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $wpCommentIdToMfBlogCommentIdMap = [];

    /**
     * @var array
     */
    private $wpPostIdToMfBlogAuthorIdMap = [];

    /**
     * @param OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager
     * @param CommentRepositoryInterface $commentRepository
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager,
        CommentRepositoryInterface $commentRepository,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->oldIdsToNewIdsRelationManager = $oldIdsToNewIdsRelationManager;
        $this->commentRepository = $commentRepository;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Process comment import
     *
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $this->wpCommentIdToMfBlogCommentIdMap = $this->oldIdsToNewIdsRelationManager->getMap(Import::COMMENT);
        $this->wpPostIdToMfBlogAuthorIdMap = $this->oldIdsToNewIdsRelationManager->getMap(Import::POST);

        foreach ($data as $commentData) {
            $this->createComment($commentData);
        }

        $this->oldIdsToNewIdsRelationManager->updateMap($this->wpCommentIdToMfBlogCommentIdMap, Import::COMMENT);

        return $this->wpCommentIdToMfBlogCommentIdMap;
    }

    /**
     * Create comment
     *
     * @param array $commentData
     * @return void
     */
    private function createComment(array $commentData): void
    {
        try {
            if (empty($commentData['text']) || empty($this->wpPostIdToMfBlogAuthorIdMap[$commentData['post_id']])) {
                return;
            }

            $wpCommentId = $commentData['old_id'];
            $mfCommentId = $this->wpCommentIdToMfBlogCommentIdMap[$wpCommentId] ?? null;

            if ($mfCommentId && $this->isCommentExist((int)$mfCommentId)) {
                return;
            }

            $comment = $this->commentRepository->getFactory()->create();
            $comment->addData($this->prepareData($commentData));
            $this->commentRepository->save($comment);

            $mfCommentId = $comment->getId();
            $this->wpCommentIdToMfBlogCommentIdMap[$wpCommentId] = $mfCommentId;

        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Prepare comment data
     *
     * @param array $commentData
     * @return array
     */
    private function prepareData(array $commentData): array
    {
        $mfBlogPostId = $this->wpPostIdToMfBlogAuthorIdMap[$commentData['post_id']];
        $commentParentId = 0;

        if (!($commentData['parent_id'] == 0)
            && isset($this->wpCommentIdToMfBlogCommentIdMap[$commentData["parent_id"]])) {
            $commentParentId = $this->wpCommentIdToMfBlogCommentIdMap[$commentData["parent_id"]];
        }

        $preparedCommentData = [
            'parent_id' => $commentParentId,
            'post_id' => $mfBlogPostId,
            'status' => \Magefan\Blog\Model\Config\Source\CommentStatus::APPROVED,
            'author_type' => \Magefan\Blog\Model\Config\Source\AuthorType::GUEST,
            'author_nickname' => $commentData['author_nickname'],
            'author_email' => $commentData['author_email'],
            'text' => $commentData['text'],
            'creation_time' => $commentData['creation_time']
        ];

        return $preparedCommentData;
    }

    /**
     * Check comment exist
     *
     * @param int $id
     * @return bool
     */
    private function isCommentExist(int $id): bool
    {
        try {
            $this->commentRepository->getById($id);
                return true;
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }

        return false;
    }
}
