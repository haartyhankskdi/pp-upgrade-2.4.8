<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\AdvisePost\Model;

use Kdi\AdvisePost\Api\Data\AdvicePostInterface;
use Magento\Framework\Model\AbstractModel;

class AdvicePost extends AbstractModel implements AdvicePostInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Kdi\AdvisePost\Model\ResourceModel\AdvicePost::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entity_id)
    {
        return $this->setData(self::ENTITY_ID, $entity_id);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getReviewBy()
    {
        return $this->getData(self::REVIEW_BY);
    }

    /**
     * @inheritDoc
     */
    public function setReviewBy($reviewBy)
    {
        return $this->setData(self::REVIEW_BY, $reviewBy);
    }

    /**
     * @inheritDoc
     */
    public function getAuthorBy()
    {
        return $this->getData(self::AUTHOR_BY);
    }

    /**
     * @inheritDoc
     */
    public function setAuthorBy($authorBy)
    {
        return $this->setData(self::AUTHOR_BY, $authorBy);
    }

    /**
     * @inheritDoc
     */
    public function getProducts()
    {
        return $this->getData(self::PRODUCTS);
    }

    /**
     * @inheritDoc
     */
    public function setProducts($products)
    {
        return $this->setData(self::PRODUCTS, $products);
    }

    /**
     * @inheritDoc
     */
    public function getConsultationUrl()
    {
        return $this->getData(self::CONSULTATION_URL);
    }

    /**
     * @inheritDoc
     */
    public function setConsultationUrl($consultationUrl)
    {
        return $this->setData(self::CONSULTATION_URL, $consultationUrl);
    }

    /**
     * @inheritDoc
     */
    public function getBlogCategoryId()
    {
        return $this->getData(self::BLOG_CATEGORY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setBlogCategoryId($blogCategoryId)
    {
        return $this->setData(self::BLOG_CATEGORY_ID, $blogCategoryId);
    }

    /**
     * @inheritDoc
     */
    public function getCategoryId()
    {
        return $this->getData(self::CATEGORY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCategoryId($categoryId)
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
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
}

