<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\ContactDB\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ContactDBRepositoryInterface
{

    /**
     * Save ContactDB
     * @param \Nilesh\ContactDB\Api\Data\ContactDBInterface $contactDB
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Nilesh\ContactDB\Api\Data\ContactDBInterface $contactDB
    );

    /**
     * Retrieve ContactDB
     * @param string $contactdbId
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($contactdbId);

    /**
     * Retrieve ContactDB matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Nilesh\ContactDB\Api\Data\ContactDBSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ContactDB
     * @param \Nilesh\ContactDB\Api\Data\ContactDBInterface $contactDB
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Nilesh\ContactDB\Api\Data\ContactDBInterface $contactDB
    );

    /**
     * Delete ContactDB by ID
     * @param string $contactdbId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($contactdbId);
}

