<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Api\Data;

interface AgeverificationSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get ageverification list.
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface[]
     */
    public function getItems();

    /**
     * Set ageverification_id list.
     * @param \Sachin\Customer\Api\Data\AgeverificationInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

