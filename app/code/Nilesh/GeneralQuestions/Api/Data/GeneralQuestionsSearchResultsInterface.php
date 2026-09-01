<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Api\Data;

interface GeneralQuestionsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get GeneralQuestions list.
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface[]
     */
    public function getItems();

    /**
     * Set customer_id list.
     * @param \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

