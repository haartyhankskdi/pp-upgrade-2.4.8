<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Api\Data;

interface EntitySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Entity list.
     * @return \Kdi\ImageUpload\Api\Data\EntityInterface[]
     */
    public function getItems();

    /**
     * Set full_image list.
     * @param \Kdi\ImageUpload\Api\Data\EntityInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

