<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\LNAPI\Model\Data;

use Haartyhanks\LNAPI\Api\Data\EntityInterface;

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
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get Customer_Id
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * Set Customer_Id
     * @param string $customerId
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Haartyhanks\LNAPI\Api\Data\EntityExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Haartyhanks\LNAPI\Api\Data\EntityExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Haartyhanks\LNAPI\Api\Data\EntityExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get verification_link
     * @return string|null
     */
    public function getVerificationLink()
    {
        return $this->_get(self::VERIFICATION_LINK);
    }

    /**
     * Set verification_link
     * @param string $verificationLink
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setVerificationLink($verificationLink)
    {
        return $this->setData(self::VERIFICATION_LINK, $verificationLink);
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
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get is_verified
     * @return string|null
     */
    public function getIsVerified()
    {
        return $this->_get(self::IS_VERIFIED);
    }

    /**
     * Set is_verified
     * @param string $isVerified
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setIsVerified($isVerified)
    {
        return $this->setData(self::IS_VERIFIED, $isVerified);
    }

    /**
     * Get is_failed
     * @return string|null
     */
    public function getIsFailed()
    {
        return $this->_get(self::IS_FAILED);
    }

    /**
     * Set is_failed
     * @param string $isFailed
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setIsFailed($isFailed)
    {
        return $this->setData(self::IS_FAILED, $isFailed);
    }

    /**
     * Get attempt
     * @return string|null
     */
    public function getAttempt()
    {
        return $this->_get(self::ATTEMPT);
    }

    /**
     * Set attempt
     * @param string $attempt
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setAttempt($attempt)
    {
        return $this->setData(self::ATTEMPT, $attempt);
    }
}

