<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface AgeverificationRepositoryInterface
{

    /**
     * Save ageverification
     * @param \Sachin\Customer\Api\Data\AgeverificationInterface $ageverification
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Sachin\Customer\Api\Data\AgeverificationInterface $ageverification
    );

    /**
     * Retrieve ageverification
     * @param string $ageverificationId
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($ageverificationId);

    /**
     * Retrieve ageverification matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Sachin\Customer\Api\Data\AgeverificationSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ageverification
     * @param \Sachin\Customer\Api\Data\AgeverificationInterface $ageverification
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Sachin\Customer\Api\Data\AgeverificationInterface $ageverification
    );

    /**
     * Delete ageverification by ID
     * @param string $ageverificationId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($ageverificationId);
}

