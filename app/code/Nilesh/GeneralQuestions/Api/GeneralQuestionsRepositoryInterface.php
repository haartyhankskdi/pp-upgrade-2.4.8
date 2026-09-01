<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface GeneralQuestionsRepositoryInterface
{

    /**
     * Save GeneralQuestions
     * @param \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface $generalQuestions
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface $generalQuestions
    );

    /**
     * Retrieve GeneralQuestions
     * @param string $generalquestionsId
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($generalquestionsId);

    /**
     * Retrieve GeneralQuestions matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete GeneralQuestions
     * @param \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface $generalQuestions
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface $generalQuestions
    );

    /**
     * Delete GeneralQuestions by ID
     * @param string $generalquestionsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($generalquestionsId);
}

