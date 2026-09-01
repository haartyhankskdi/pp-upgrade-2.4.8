<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\ContactDB\Api\Data;

interface ContactDBInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const CONTACT = 'contact';
    const CREATED_DATE = 'created_date';
    const EMAIL = 'email';
    const NAME = 'name';
    const COMMENT = 'comment';
    const CONTACTDB_ID = 'contactdb_id';
    const PREFERENCE = 'preference';

    /**
     * Get contactdb_id
     * @return string|null
     */
    public function getContactdbId();

    /**
     * Set contactdb_id
     * @param string $contactdbId
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setContactdbId($contactdbId);

    /**
     * Get name
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setName($name);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Nilesh\ContactDB\Api\Data\ContactDBExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Nilesh\ContactDB\Api\Data\ContactDBExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Nilesh\ContactDB\Api\Data\ContactDBExtensionInterface $extensionAttributes
    );

    /**
     * Get email
     * @return string|null
     */
    public function getEmail();

    /**
     * Set email
     * @param string $email
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setEmail($email);

    /**
     * Get contact
     * @return string|null
     */
    public function getContact();

    /**
     * Set contact
     * @param string $contact
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setContact($contact);

    /**
     * Get comment
     * @return string|null
     */
    public function getComment();

    /**
     * Set comment
     * @param string $comment
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setComment($comment);

    /**
     * Get preference
     * @return string|null
     */
    public function getPreference();

    /**
     * Set preference
     * @param string $preference
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setPreference($preference);

    /**
     * Get created_date
     * @return string|null
     */
    public function getCreatedDate();

    /**
     * Set created_date
     * @param string $createdDate
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setCreatedDate($createdDate);
}

