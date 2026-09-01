<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface EntityRepositoryInterface
{

    /**
     * Save Entity
     * @param \Kdi\ImageUpload\Api\Data\EntityInterface $entity
     * @return \Kdi\ImageUpload\Api\Data\EntityInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Kdi\ImageUpload\Api\Data\EntityInterface $entity
    );

    /**
     * Retrieve Entity
     * @param string $entityId
     * @return \Kdi\ImageUpload\Api\Data\EntityInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($entityId);

    /**
     * Retrieve Entity matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Kdi\ImageUpload\Api\Data\EntitySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Entity
     * @param \Kdi\ImageUpload\Api\Data\EntityInterface $entity
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Kdi\ImageUpload\Api\Data\EntityInterface $entity
    );

    /**
     * Delete Entity by ID
     * @param string $entityId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($entityId);
}

