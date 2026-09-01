<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GpManagement\Api\Data;

interface GpManagementSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get GpManagement list.
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface[]
     */
    public function getItems();

    /**
     * Set practice_code list.
     * @param \Nilesh\GpManagement\Api\Data\GpManagementInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

