<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Testimonials\Api\Data;

interface TestimonialsInterface
{

    const UPDATED_AT = 'updated_at';
    const PRODUCT_ID = 'product_id';
    const REVIEW = 'Review';
    const TITLE = 'title';
    const CREATED_AT = 'created_at';
    const IMAGE1 = 'image1';
    const TESTIMONIALS_ID = 'testimonials_id';
    const IMAGE2 = 'image2';
    const META_URL = 'meta_url';
    const META_TITLE = 'meta_title';
    const META_DESC = 'meta_desc';
    const META_KEYWORD = 'meta_keyword';
    const CATEGORY_ID = 'category_id';
    const REVIEW_WRITER = 'review_writer';
    const REVIEW_DATE = 'review_date';
    const PRODUCT_CONSUMING_START_FROM = 'product_consuming_start_from';
    const STATUS = 'status';
    const ROBOTS = 'robots';

    /**
     * Get testimonials_id
     * @return string|null
     */
    public function getTestimonialsId();

    /**
     * Set testimonials_id
     * @param string $testimonialsId
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setTestimonialsId($testimonialsId);

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId();

    /**
     * Set product_id
     * @param string $productId
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setProductId($productId);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get title
     * @return string|null
     */
    public function getTitle();

    /**
     * Set title
     * @param string $title
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setTitle($title);

    /**
     * Get Review
     * @return string|null
     */
    public function getReview();

    /**
     * Set Review
     * @param string $review
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setReview($review);

    /**
     * Get image1
     * @return string|null
     */
    public function getImage1();

    /**
     * Set image1
     * @param string $image1
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setImage1($image1);

    /**
     * Get image2
     * @return string|null
     */
    public function getImage2();

    /**
     * Set image2
     * @param string $image2
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setImage2($image2);


    /**
     * Get meta title
     * @return string|null
     */
    public function getMetaTitle();

    /**
     * Set meta title
     * @param string $metaTitle
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setMetaTitle($metaTitle);

    /**
     * Get meta description
     * @return string|null
     */
    public function getMetaDesc();

    /**
     * Set meta description
     * @param string $metaDesc
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setMetaDesc($metaDesc);

    /**
     * Get meta keyword
     * @return string|null
     */
    public function getMetaKeyword();

    /**
     * Set meta keyword
     * @param string $metaKeyword
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setMetaKeyword($metaKeyword);

    /**
     * Get category
     * @return string|null
     */
    public function getCategory();

    /**
     * Set category
     * @param string $category
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setCategory($category);

    /**
     * Get meta URL
     * @return string|null
     */
    public function getMetaUrl();

    /**
     * Set meta URL
     * @param string $metaUrl
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setMetaUrl($metaUrl);


    /**
     * Get review writer
     * @return string|null
     */
    public function getReviewWriter();

    /**
     * Set review writer
     * @param string $reviewWriter
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setReviewWriter($reviewWriter);

    /**
     * Get review date
     * @return string|null
     */
    public function getReviewDate();

    /**
     * Set review date
     * @param string $reviewDate
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setReviewDate($reviewDate);

    /**
     * Get product consuming start from
     * @return string|null
     */
    public function getProductConsumingStartFrom();

    /**
     * Set product consuming start from
     * @param string $productConsumingStartFrom
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setProductConsumingStartFrom($productConsumingStartFrom);


    /**
     * Get status
     * @return int|null
     */
    public function getStatus();

    /**
     * Set status
     * @param int $status
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setStatus($status);

    /**
     * Get robots
     * @return int|null
     */
    public function getRobots();

    /**
     * Set robots
     * @param int $robots
     * @return \Kdi\Testimonials\Testimonials\Api\Data\TestimonialsInterface
     */
    public function setRobots($robots);

    
}

