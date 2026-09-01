<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\AdvisePost\Api\Data;

interface AdvicePostInterface
{

    const CATEGORY_ID = 'category_id';
    const ENTITY_ID = 'entity_id';
    const BLOG_CATEGORY_ID = 'blog_category_id';
    const NAME = 'name';
    const PRODUCTS = 'products';
    const TITLE = 'title';
    const CONSULTATION_URL = 'consultation_url';
    const DESCRIPTION = 'description';
    const REVIEW_BY = 'review_by';
    const AUTHOR_BY = 'author_by';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entity_id
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setEntityId($entity_id);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setName($name);

    /**
     * Get description
     * @return string|null
     */
    public function getDescription();

    /**
     * Set description
     * @param string $description
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setDescription($description);

    /**
     * Get review_by
     * @return string|null
     */
    public function getReviewBy();

    /**
     * Set review_by
     * @param string $reviewBy
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setReviewBy($reviewBy);

    /**
     * Get author_by
     * @return string|null
     */
    public function getAuthorBy();

    /**
     * Set author_by
     * @param string $authorBy
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setAuthorBy($authorBy);

    /**
     * Get products
     * @return string|null
     */
    public function getProducts();

    /**
     * Set products
     * @param string $products
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setProducts($products);

    /**
     * Get consultation_url
     * @return string|null
     */
    public function getConsultationUrl();

    /**
     * Set consultation_url
     * @param string $consultationUrl
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setConsultationUrl($consultationUrl);

    /**
     * Get blog_category_id
     * @return string|null
     */
    public function getBlogCategoryId();

    /**
     * Set blog_category_id
     * @param string $blogCategoryId
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setBlogCategoryId($blogCategoryId);

    /**
     * Get category_id
     * @return string|null
     */
    public function getCategoryId();

    /**
     * Set category_id
     * @param string $categoryId
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setCategoryId($categoryId);

    /**
     * Get title
     * @return string|null
     */
    public function getTitle();

    /**
     * Set title
     * @param string $title
     * @return \Kdi\AdvisePost\AdvicePost\Api\Data\AdvicePostInterface
     */
    public function setTitle($title);
}

