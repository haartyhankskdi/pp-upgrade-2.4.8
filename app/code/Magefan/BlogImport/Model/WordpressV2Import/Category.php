<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogImport\Model\WordpressV2Import;

use Magefan\Blog\Api\CategoryRepositoryInterface;
use Magefan\BlogImport\Model\WordpressV2Import\Import;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magefan\BlogImport\Model\WordpressV2Import\OldIdsToNewIdsRelationManager;

class Category
{
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OldIdsToNewIdsRelationManager
     */
    private $oldIdsToNewIdsRelationManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var array
     */
    private $wpCatIdToMfBlogCatIdMap = [];

    /**
     * @var null
     */
    private $existingCategoryIdentifiers = null;

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param LoggerInterface $logger
     * @param \Magefan\BlogImport\Model\WordpressV2Import\OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        LoggerInterface $logger,
        OldIdsToNewIdsRelationManager $oldIdsToNewIdsRelationManager,
        ResourceConnection $resourceConnection
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
        $this->oldIdsToNewIdsRelationManager = $oldIdsToNewIdsRelationManager;
        $this->resourceConnection = $resourceConnection;
        $this->connection = $this->resourceConnection->getConnection();
    }

    /**
     * Process category import
     *
     * @param array $categoriesData
     * @return array
     */
    public function execute(array $categoriesData): array
    {
        $this->wpCatIdToMfBlogCatIdMap = $this->oldIdsToNewIdsRelationManager->getMap(Import::CATEGORY);

        foreach ($categoriesData as $categoryData) {
            $this->createCategory($categoryData);
        }

        $this->updateCategoriesPath($categoriesData);

        $this->oldIdsToNewIdsRelationManager->updateMap($this->wpCatIdToMfBlogCatIdMap, Import::CATEGORY);

        return $this->wpCatIdToMfBlogCatIdMap;
    }

    /**
     * Create category
     *
     * @param array $data
     * @return void
     */
    private function createCategory(array $data): void
    {
        $wpCatId = $data['old_id'];

        $mfBlogCatId = array_search(
            $data['identifier'],
            $this->getExistingCategoryIdentifiers()
        );

        if ($mfBlogCatId !== false) {
            $this->wpCatIdToMfBlogCatIdMap[$wpCatId] = $mfBlogCatId;
            return;
        }

        $preparedData = $this->prepareData($data);

        $category = $this->categoryRepository->getFactory()
            ->create()
            ->setData($preparedData);

        try {
            $this->categoryRepository->save($category);
            $mfBlogCategoryId = $category->getId();

            $this->wpCatIdToMfBlogCatIdMap[$wpCatId] = $mfBlogCategoryId;
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * Update categories path
     *
     * @param array $categoriesData
     * @return void
     */
    private function updateCategoriesPath(array $categoriesData): void
    {
        $categories = [];

        foreach ($categoriesData as $catData) {
            $wpId = $catData['old_id'];
            $wpParentId = $catData['parent_id'];

            $mfId = $this->wpCatIdToMfBlogCatIdMap[$wpId] ?? null;
            $mfParentId = $this->wpCatIdToMfBlogCatIdMap[$wpParentId] ?? null;

            if ($mfId) {
                $category = $this->categoryRepository->getById($mfId);

                if ($mfParentId) {
                    $category->setPath($mfParentId);
                }

                $categories[$mfId] = $category;
            }
        }

        for ($i = 0; $i < 4; $i++) {
            $changed = false;
            foreach ($categories as $ct) {
                if ($ct->getPath()) {
                    $parentId = explode('/', (string)$ct->getPath())[0];

                    if (empty($categories[$parentId])) {
                        continue;
                    }

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

        foreach ($categories as $ct) {
            /* Final saving */
            try {
                $this->categoryRepository->save($ct);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Prepare category data
     *
     * @param array $wpData
     * @return array
     */
    private function prepareData(array $wpData): array
    {
        $data = [
            'title' => $wpData['title'],
            'include_in_menu' => 1,
            'position' => 0,
            'path' => 0,
            'store_ids' => [0],
            'content' => empty($wpData['description']) ? '' : $wpData['description'],
            'identifier' => $wpData['identifier']
        ];

        // 1 is default value
        if (isset($wpData['is_active']) && (int)$wpData['is_active'] === 0) {
            $data['is_active'] = 0;
        }

        return $data;
    }

    /**
     * Get existing category identifiers
     *
     * @return array
     */
    private function getExistingCategoryIdentifiers(): array
    {
        if (null === $this->existingCategoryIdentifiers) {
            $tableName = $this->resourceConnection->getTableName('magefan_blog_category');
            $select = $this->connection->select()->from($tableName, ['category_id', 'identifier']);
            $this->existingCategoryIdentifiers = $this->connection->fetchPairs($select);
        }

        return $this->existingCategoryIdentifiers;
    }
}
