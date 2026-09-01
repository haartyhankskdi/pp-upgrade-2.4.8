<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\AuthReview\Api\Data;

interface EntityInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const REGISTRATION_NUMBER = 'registration_number';
    const EXTERNAL_LINK = 'external_link';
    const DEFAULT_AUTHOR = 'default_author';
    const SPECIALIST = 'specialist';
    const BIO = 'bio';
    const NAME = 'name';
    const PHOTO = 'photo';
    const DEFAULT_REVIEW = 'default_review';
    const CREDENTIALS = 'credentials';
    const STATUS = 'status';
    const ENTITY_ID = 'entity_id';
    const ARTICLES = 'articles';
    const SCHEMA = 'schema';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setEntityId($entityId);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setName($name);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Haartyhanks\AuthReview\Api\Data\EntityExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Haartyhanks\AuthReview\Api\Data\EntityExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Haartyhanks\AuthReview\Api\Data\EntityExtensionInterface $extensionAttributes
    );

    /**
     * Get photo
     * @return string|null
     */
    public function getPhoto();

    /**
     * Set photo
     * @param string $photo
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setPhoto($photo);

    /**
     * Get credentials
     * @return string|null
     */
    public function getCredentials();

    /**
     * Set credentials
     * @param string $credentials
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setCredentials($credentials);

    /**
     * Get specialist
     * @return string|null
     */
    public function getSpecialist();

    /**
     * Set specialist
     * @param string $specialist
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setSpecialist($specialist);

    /**
     * Get registration_number
     * @return string|null
     */
    public function getRegistrationNumber();

    /**
     * Set registration_number
     * @param string $registrationNumber
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setRegistrationNumber($registrationNumber);

    /**
     * Get bio
     * @return string|null
     */
    public function getBio();

    /**
     * Set bio
     * @param string $bio
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setBio($bio);

    /**
     * Get external_link
     * @return string|null
     */
    public function getExternalLink();

    /**
     * Set external_link
     * @param string $externalLink
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setExternalLink($externalLink);

    /**
     * Get articles
     * @return string|null
     */
    public function getArticles();

    /**
     * Set articles
     * @param string $articles
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setArticles($articles);

    /**
     * Get schema
     * @return string|null
     */
    public function getSchema();

    /**
     * Set schema
     * @param string $schema
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setSchema($schema);

    /**
     * Get default_review
     * @return string|null
     */
    public function getDefaultReview();

    /**
     * Set default_review
     * @param string $defaultReview
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setDefaultReview($defaultReview);

    /**
     * Get default_author
     * @return string|null
     */
    public function getDefaultAuthor();

    /**
     * Set default_author
     * @param string $defaultAuthor
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setDefaultAuthor($defaultAuthor);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Haartyhanks\AuthReview\Api\Data\EntityInterface
     */
    public function setStatus($status);
}

