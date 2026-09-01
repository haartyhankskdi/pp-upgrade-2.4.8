<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Testimonials\Api\Data;

interface TestimonialsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Testimonials list.
     * @return \Kdi\Testimonials\Api\Data\TestimonialsInterface[]
     */
    public function getItems();

    /**
     * Set product_id list.
     * @param \Kdi\Testimonials\Api\Data\TestimonialsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

