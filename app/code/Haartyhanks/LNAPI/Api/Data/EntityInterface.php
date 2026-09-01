<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\LNAPI\Api\Data;

interface EntityInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const CUSTOMER_ID = 'Customer_Id';
    const VERIFICATION_LINK = 'verification_link';
    const STATUS = 'status';
    const IS_VERIFIED = 'is_verified';
    const ATTEMPT = 'attempt';
    const ENTITY_ID = 'entity_id';
    const IS_FAILED = 'is_failed';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setEntityId($entityId);

    /**
     * Get Customer_Id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set Customer_Id
     * @param string $customerId
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setCustomerId($customerId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Haartyhanks\LNAPI\Api\Data\EntityExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Haartyhanks\LNAPI\Api\Data\EntityExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Haartyhanks\LNAPI\Api\Data\EntityExtensionInterface $extensionAttributes
    );

    /**
     * Get verification_link
     * @return string|null
     */
    public function getVerificationLink();

    /**
     * Set verification_link
     * @param string $verificationLink
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setVerificationLink($verificationLink);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setStatus($status);

    /**
     * Get is_verified
     * @return string|null
     */
    public function getIsVerified();

    /**
     * Set is_verified
     * @param string $isVerified
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setIsVerified($isVerified);

    /**
     * Get is_failed
     * @return string|null
     */
    public function getIsFailed();

    /**
     * Set is_failed
     * @param string $isFailed
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setIsFailed($isFailed);

    /**
     * Get attempt
     * @return string|null
     */
    public function getAttempt();

    /**
     * Set attempt
     * @param string $attempt
     * @return \Haartyhanks\LNAPI\Api\Data\EntityInterface
     */
    public function setAttempt($attempt);
}

