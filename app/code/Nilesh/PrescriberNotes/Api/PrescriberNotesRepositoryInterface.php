<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface PrescriberNotesRepositoryInterface
{

    /**
     * Save PrescriberNotes
     * @param \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface $prescriberNotes
     * @return \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface $prescriberNotes
    );

    /**
     * Retrieve PrescriberNotes
     * @param string $prescribernotesId
     * @return \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($prescribernotesId);

    /**
     * Retrieve PrescriberNotes matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete PrescriberNotes
     * @param \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface $prescriberNotes
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface $prescriberNotes
    );

    /**
     * Delete PrescriberNotes by ID
     * @param string $prescribernotesId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($prescribernotesId);
}

