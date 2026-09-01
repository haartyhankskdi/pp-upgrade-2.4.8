<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\AdvisePost\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface AdvicePostRepositoryInterface
{

    /**
     * Save AdvicePost
     * @param \Kdi\AdvisePost\Api\Data\AdvicePostInterface $advicePost
     * @return \Kdi\AdvisePost\Api\Data\AdvicePostInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Kdi\AdvisePost\Api\Data\AdvicePostInterface $advicePost
    );

    /**
     * Retrieve AdvicePost
     * @param string $advicepostId
     * @return \Kdi\AdvisePost\Api\Data\AdvicePostInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($advicepostId);

    /**
     * Retrieve AdvicePost matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Kdi\AdvisePost\Api\Data\AdvicePostSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete AdvicePost
     * @param \Kdi\AdvisePost\Api\Data\AdvicePostInterface $advicePost
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Kdi\AdvisePost\Api\Data\AdvicePostInterface $advicePost
    );

    /**
     * Delete AdvicePost by ID
     * @param string $advicepostId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($advicepostId);
}

