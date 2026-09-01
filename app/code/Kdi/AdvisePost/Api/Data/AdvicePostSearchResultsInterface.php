<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\AdvisePost\Api\Data;

interface AdvicePostSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get AdvicePost list.
     * @return \Kdi\AdvisePost\Api\Data\AdvicePostInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     * @param \Kdi\AdvisePost\Api\Data\AdvicePostInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

