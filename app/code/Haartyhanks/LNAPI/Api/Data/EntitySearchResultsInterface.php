<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\LNAPI\Api\Data;

interface EntitySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Entity list.
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface[]
     */
    public function getItems();

    /**
     * Set Customer_Id list.
     * @param \Haartyhanks\LNAPI\Api\Data\EntityInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

