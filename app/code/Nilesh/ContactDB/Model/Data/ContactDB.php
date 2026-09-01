<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\ContactDB\Model\Data;

use Nilesh\ContactDB\Api\Data\ContactDBInterface;

class ContactDB extends \Magento\Framework\Api\AbstractExtensibleObject implements ContactDBInterface
{

    /**
     * Get contactdb_id
     * @return string|null
     */
    public function getContactdbId()
    {
        return $this->_get(self::CONTACTDB_ID);
    }

    /**
     * Set contactdb_id
     * @param string $contactdbId
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setContactdbId($contactdbId)
    {
        return $this->setData(self::CONTACTDB_ID, $contactdbId);
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
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Nilesh\ContactDB\Api\Data\ContactDBExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Nilesh\ContactDB\Api\Data\ContactDBExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Nilesh\ContactDB\Api\Data\ContactDBExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get email
     * @return string|null
     */
    public function getEmail()
    {
        return $this->_get(self::EMAIL);
    }

    /**
     * Set email
     * @param string $email
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Get contact
     * @return string|null
     */
    public function getContact()
    {
        return $this->_get(self::CONTACT);
    }

    /**
     * Set contact
     * @param string $contact
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setContact($contact)
    {
        return $this->setData(self::CONTACT, $contact);
    }

    /**
     * Get comment
     * @return string|null
     */
    public function getComment()
    {
        return $this->_get(self::COMMENT);
    }

    /**
     * Set comment
     * @param string $comment
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setComment($comment)
    {
        return $this->setData(self::COMMENT, $comment);
    }

    /**
     * Get preference
     * @return string|null
     */
    public function getPreference()
    {
        return $this->_get(self::PREFERENCE);
    }

    /**
     * Set preference
     * @param string $preference
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setPreference($preference)
    {
        return $this->setData(self::PREFERENCE, $preference);
    }

    /**
     * Get created_date
     * @return string|null
     */
    public function getCreatedDate()
    {
        return $this->_get(self::CREATED_DATE);
    }

    /**
     * Set created_date
     * @param string $createdDate
     * @return \Nilesh\ContactDB\Api\Data\ContactDBInterface
     */
    public function setCreatedDate($createdDate)
    {
        return $this->setData(self::CREATED_DATE, $createdDate);
    }
}

