<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\AuthReview\Api\Data;

interface EntitySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Entity list.
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     * @param \Haartyhanks\AuthReview\Api\Data\EntityInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

