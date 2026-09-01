<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Testimonials\Model;

use Kdi\Testimonials\Api\Data\TestimonialsInterface;
use Magento\Framework\Model\AbstractModel;

class Testimonials extends AbstractModel implements TestimonialsInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Kdi\Testimonials\Model\ResourceModel\Testimonials::class);
    }

    /**
     * @inheritDoc
     */
    public function getTestimonialsId()
    {
        return $this->getData(self::TESTIMONIALS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setTestimonialsId($testimonialsId)
    {
        return $this->setData(self::TESTIMONIALS_ID, $testimonialsId);
    }

    /**
     * @inheritDoc
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @inheritDoc
     */
    public function getReview()
    {
        return $this->getData(self::REVIEW);
    }

    /**
     * @inheritDoc
     */
    public function setReview($review)
    {
        return $this->setData(self::REVIEW, $review);
    }

    /**
     * @inheritDoc
     */
    public function getImage1()
    {
        return $this->getData(self::IMAGE1);
    }

    /**
     * @inheritDoc
     */
    public function setImage1($image1)
    {
        return $this->setData(self::IMAGE1, $image1);
    }

    /**
     * @inheritDoc
     */
    public function getImage2()
    {
        return $this->getData(self::IMAGE2);
    }

    /**
     * @inheritDoc
     */
    public function setImage2($image2)
    {
        return $this->setData(self::IMAGE2, $image2);
    }


    /**
     * @inheritDoc
     */
    public function getMetaTitle()
    {
        return $this->getData(self::META_TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setMetaTitle($metaTitle)
    {
        return $this->setData(self::META_TITLE, $metaTitle);
    }

    /**
     * @inheritDoc
     */
    public function getMetaDesc()
    {
        return $this->getData(self::META_DESC);
    }

    /**
     * @inheritDoc
     */
    public function setMetaDesc($metaDesc)
    {
        return $this->setData(self::META_DESC, $metaDesc);
    }

    /**
     * @inheritDoc
     */
    public function getMetaKeyword()
    {
        return $this->getData(self::META_KEYWORD);
    }

    /**
     * @inheritDoc
     */
    public function setMetaKeyword($metaKeyword)
    {
        return $this->setData(self::META_KEYWORD, $metaKeyword);
    }

    /**
     * @inheritDoc
     */
    public function getCategory()
    {
        return $this->getData(self::CATEGORY);
    }

    /**
     * @inheritDoc
     */
    public function setCategory($category)
    {
        return $this->setData(self::CATEGORY, $category);
    }

    /**
     * @inheritDoc
     */
    public function getMetaUrl()
    {
        return $this->getData(self::META_URL);
    }

    /**
     * @inheritDoc
     */
    public function setMetaUrl($metaUrl)
    {
        return $this->setData(self::META_URL, $metaUrl);
    }
    /**
     * @inheritDoc
     */
    public function getReviewWriter()
    {
        return $this->getData(self::REVIEW_WRITER);
    }

    /**
     * @inheritDoc
     */
    public function setReviewWriter($reviewWriter)
    {
        return $this->setData(self::REVIEW_WRITER, $reviewWriter);
    }

    /**
     * @inheritDoc
     */
    public function getReviewDate()
    {
        return $this->getData(self::REVIEW_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setReviewDate($reviewDate)
    {
        return $this->setData(self::REVIEW_DATE, $reviewDate);
    }

    /**
     * @inheritDoc
     */
    public function getProductConsumingStartFrom()
    {
        return $this->getData(self::PRODUCT_CONSUMING_START_FROM);
    }

    /**
     * @inheritDoc
     */
    public function setProductConsumingStartFrom($productConsumingStartFrom)
    {
        return $this->setData(self::PRODUCT_CONSUMING_START_FROM, $productConsumingStartFrom);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getRobots()
    {
        return $this->getData(self::ROBOTS);
    }

    /**
     * @inheritDoc
     */
    public function setRobots($robots)
    {
        return $this->setData(self::ROBOTS, $robots);
    }
}

