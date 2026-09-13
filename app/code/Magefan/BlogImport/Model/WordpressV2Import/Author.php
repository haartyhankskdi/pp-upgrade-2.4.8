<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogImport\Model\WordpressV2Import;

use Magefan\BlogImport\Model\WordpressV2Import\Import;
use Magento\Framework\Module\Manager as ModuleManager;
use Magefan\Blog\Api\AuthorRepositoryInterface;
use Magefan\BlogImport\Model\WordpressV2Import\OldIdsToNewIdsRelationManager;

class Author
{
    /**
     * @var AuthorRepositoryInterface
     */
    private $authorRepository;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var OldIdsToNewIdsRelationManager
     */
    private $oldIdsToNewIdsRelationManager;

    /**
     * @param AuthorRepositoryInterface $authorRepository
     * @param ModuleManager $moduleManager
     * @param OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager
     */
    public function __construct(
        AuthorRepositoryInterface $authorRepository,
        ModuleManager $moduleManager,
        OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager
    ) {
        $this->authorRepository = $authorRepository;
        $this->moduleManager = $moduleManager;
        $this->oldIdsToNewIdsRelationManager = $oldIdsToNewIdsRelationManager;
    }

    /**
     * Executes the process of migrating author data and updating the ID mapping.
     *
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        if (!$this->moduleManager->isEnabled('Magefan_BlogAuthor')) {
            return [];
        }

        $wpAuthorIdToMfBlogAuthorIdMap = [];

        foreach ($data as $authorData) {
            $preparedAuthorData = [
                'firstname' => $authorData['firstname'],
                'lastname' => $authorData['lastname'],
                'content' => $authorData['content'],
                'email' => $authorData['email']
            ];

            try {
                $author = $this->authorRepository->getFactory()->create();
                $author->setData($preparedAuthorData);
                $this->authorRepository->save($author);

                $wpAuthorId = $authorData['old_id'];
                $mfAuthorId = $author->getId();
                $wpAuthorIdToMfBlogAuthorIdMap[$wpAuthorId] = $mfAuthorId;
            } catch (\Exception $e) {
                return [];
            }
        }

        $this->oldIdsToNewIdsRelationManager->updateMap($wpAuthorIdToMfBlogAuthorIdMap, Import::AUTHOR);

        return $wpAuthorIdToMfBlogAuthorIdMap;
    }
}
