<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Testimonials\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface TestimonialsRepositoryInterface
{

    /**
     * Save Testimonials
     * @param \Kdi\Testimonials\Api\Data\TestimonialsInterface $testimonials
     * @return \Kdi\Testimonials\Api\Data\TestimonialsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Kdi\Testimonials\Api\Data\TestimonialsInterface $testimonials
    );

    /**
     * Retrieve Testimonials
     * @param string $testimonialsId
     * @return \Kdi\Testimonials\Api\Data\TestimonialsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($testimonialsId);

    /**
     * Retrieve Testimonials matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Kdi\Testimonials\Api\Data\TestimonialsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Testimonials
     * @param \Kdi\Testimonials\Api\Data\TestimonialsInterface $testimonials
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Kdi\Testimonials\Api\Data\TestimonialsInterface $testimonials
    );

    /**
     * Delete Testimonials by ID
     * @param string $testimonialsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($testimonialsId);
}

