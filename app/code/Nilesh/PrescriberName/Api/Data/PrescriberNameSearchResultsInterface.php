<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Api\Data;

interface PrescriberNameSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get PrescriberName list.
     * @return \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface[]
     */
    public function getItems();

    /**
     * Set name list.
     * @param \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

