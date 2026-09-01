<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\AuthReview\Model\Data;

use Haartyhanks\AuthReview\Api\Data\EntityInterface;

class Entity extends \Magento\Framework\Api\AbstractExtensibleObject implements EntityInterface
{

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get name
     * @return string|null
     */
    public function getName()
    {
        return $this->_get(self::NAME);
    }

    /**
     * Set name
     * @param string $name
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Haartyhanks\AuthReview\Api\Data\EntityExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Haartyhanks\AuthReview\Api\Data\EntityExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Haartyhanks\AuthReview\Api\Data\EntityExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get photo
     * @return string|null
     */
    public function getPhoto()
    {
        return $this->_get(self::PHOTO);
    }

    /**
     * Set photo
     * @param string $photo
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setPhoto($photo)
    {
        return $this->setData(self::PHOTO, $photo);
    }

    /**
     * Get credentials
     * @return string|null
     */
    public function getCredentials()
    {
        return $this->_get(self::CREDENTIALS);
    }

    /**
     * Set credentials
     * @param string $credentials
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setCredentials($credentials)
    {
        return $this->setData(self::CREDENTIALS, $credentials);
    }

    /**
     * Get specialist
     * @return string|null
     */
    public function getSpecialist()
    {
        return $this->_get(self::SPECIALIST);
    }

    /**
     * Set specialist
     * @param string $specialist
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setSpecialist($specialist)
    {
        return $this->setData(self::SPECIALIST, $specialist);
    }

    /**
     * Get registration_number
     * @return string|null
     */
    public function getRegistrationNumber()
    {
        return $this->_get(self::REGISTRATION_NUMBER);
    }

    /**
     * Set registration_number
     * @param string $registrationNumber
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setRegistrationNumber($registrationNumber)
    {
        return $this->setData(self::REGISTRATION_NUMBER, $registrationNumber);
    }

    /**
     * Get bio
     * @return string|null
     */
    public function getBio()
    {
        return $this->_get(self::BIO);
    }

    /**
     * Set bio
     * @param string $bio
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setBio($bio)
    {
        return $this->setData(self::BIO, $bio);
    }

    /**
     * Get external_link
     * @return string|null
     */
    public function getExternalLink()
    {
        return $this->_get(self::EXTERNAL_LINK);
    }

    /**
     * Set external_link
     * @param string $externalLink
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setExternalLink($externalLink)
    {
        return $this->setData(self::EXTERNAL_LINK, $externalLink);
    }

    /**
     * Get articles
     * @return string|null
     */
    public function getArticles()
    {
        return $this->_get(self::ARTICLES);
    }

    /**
     * Set articles
     * @param string $articles
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setArticles($articles)
    {
        return $this->setData(self::ARTICLES, $articles);
    }

    /**
     * Get schema
     * @return string|null
     */
    public function getSchema()
    {
        return $this->_get(self::SCHEMA);
    }

    /**
     * Set schema
     * @param string $schema
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setSchema($schema)
    {
        return $this->setData(self::SCHEMA, $schema);
    }

    /**
     * Get default_review
     * @return string|null
     */
    public function getDefaultReview()
    {
        return $this->_get(self::DEFAULT_REVIEW);
    }

    /**
     * Set default_review
     * @param string $defaultReview
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setDefaultReview($defaultReview)
    {
        return $this->setData(self::DEFAULT_REVIEW, $defaultReview);
    }

    /**
     * Get default_author
     * @return string|null
     */
    public function getDefaultAuthor()
    {
        return $this->_get(self::DEFAULT_AUTHOR);
    }

    /**
     * Set default_author
     * @param string $defaultAuthor
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setDefaultAuthor($defaultAuthor)
    {
        return $this->setData(self::DEFAULT_AUTHOR, $defaultAuthor);
    }

    /**
     * Get status
     * @return string|null
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * Set status
     * @param string $status
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}

