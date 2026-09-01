<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface PrescriberNameRepositoryInterface
{

    /**
     * Save PrescriberName
     * @param \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface $prescriberName
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface $prescriberName
    );

    /**
     * Retrieve PrescriberName
     * @param string $prescribernameId
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($prescribernameId);

    /**
     * Retrieve PrescriberName matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete PrescriberName
     * @param \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface $prescriberName
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface $prescriberName
    );

    /**
     * Delete PrescriberName by ID
     * @param string $prescribernameId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($prescribernameId);
}

