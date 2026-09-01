<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Api\Data;

interface AgeverificationInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const DOB = 'dob';
    const ADDRESS2 = 'address2';
    const GENDER = 'gender';
    const POSTCODE = 'postcode';
    const AGEVERIFICATION_ID = 'ageverification_id';
    const CUSTOMER_ID = 'customer_id';
    const FIRSTNAME = 'firstname';
    const ADDRESS1 = 'address1';
    const LASTNAME = 'lastname';

    /**
     * Get ageverification_id
     * @return string|null
     */
    public function getAgeverificationId();

    /**
     * Set ageverification_id
     * @param string $ageverificationId
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setAgeverificationId($ageverificationId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Sachin\Customer\Api\Data\AgeverificationExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Sachin\Customer\Api\Data\AgeverificationExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Sachin\Customer\Api\Data\AgeverificationExtensionInterface $extensionAttributes
    );

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get firstname
     * @return string|null
     */
    public function getFirstname();

    /**
     * Set firstname
     * @param string $firstname
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setFirstname($firstname);

    /**
     * Get lastname
     * @return string|null
     */
    public function getLastname();

    /**
     * Set lastname
     * @param string $lastname
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setLastname($lastname);

    /**
     * Get gender
     * @return string|null
     */
    public function getGender();

    /**
     * Set gender
     * @param string $gender
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setGender($gender);

    /**
     * Get dob
     * @return string|null
     */
    public function getDob();

    /**
     * Set dob
     * @param string $dob
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setDob($dob);

    /**
     * Get address1
     * @return string|null
     */
    public function getAddress1();

    /**
     * Set address1
     * @param string $address1
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setAddress1($address1);

    /**
     * Get address2
     * @return string|null
     */
    public function getAddress2();

    /**
     * Set address2
     * @param string $address2
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setAddress2($address2);

    /**
     * Get postcode
     * @return string|null
     */
    public function getPostcode();

    /**
     * Set postcode
     * @param string $postcode
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setPostcode($postcode);
}

