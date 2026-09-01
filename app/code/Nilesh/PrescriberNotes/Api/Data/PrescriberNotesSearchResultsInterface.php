<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Api\Data;

interface PrescriberNotesSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get PrescriberNotes list.
     * @return \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface[]
     */
    public function getItems();

    /**
     * Set id list.
     * @param \Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

