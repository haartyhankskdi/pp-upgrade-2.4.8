<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\ContactDB\Api\Data;

interface ContactDBSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get ContactDB list.
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     * @param \Nilesh\ContactDB\Api\Data\ContactDBInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

