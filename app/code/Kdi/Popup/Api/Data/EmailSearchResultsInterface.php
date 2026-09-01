<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Popup\Api\Data;

interface EmailSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Email list.
     * @return \Kdi\Popup\Api\Data\EmailInterface[]
     */
    public function getItems();

    /**
     * Set product_id list.
     * @param \Kdi\Popup\Api\Data\EmailInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

