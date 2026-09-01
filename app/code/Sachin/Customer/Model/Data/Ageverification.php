<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Model\Data;

use Sachin\Customer\Api\Data\AgeverificationInterface;

class Ageverification extends \Magento\Framework\Api\AbstractExtensibleObject implements AgeverificationInterface
{

    /**
     * Get ageverification_id
     * @return string|null
     */
    public function getAgeverificationId()
    {
        return $this->_get(self::AGEVERIFICATION_ID);
    }

    /**
     * Set ageverification_id
     * @param string $ageverificationId
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setAgeverificationId($ageverificationId)
    {
        return $this->setData(self::AGEVERIFICATION_ID, $ageverificationId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Sachin\Customer\Api\Data\AgeverificationExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Sachin\Customer\Api\Data\AgeverificationExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Sachin\Customer\Api\Data\AgeverificationExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get firstname
     * @return string|null
     */
    public function getFirstname()
    {
        return $this->_get(self::FIRSTNAME);
    }

    /**
     * Set firstname
     * @param string $firstname
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setFirstname($firstname)
    {
        return $this->setData(self::FIRSTNAME, $firstname);
    }

    /**
     * Get lastname
     * @return string|null
     */
    public function getLastname()
    {
        return $this->_get(self::LASTNAME);
    }

    /**
     * Set lastname
     * @param string $lastname
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setLastname($lastname)
    {
        return $this->setData(self::LASTNAME, $lastname);
    }

    /**
     * Get gender
     * @return string|null
     */
    public function getGender()
    {
        return $this->_get(self::GENDER);
    }

    /**
     * Set gender
     * @param string $gender
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setGender($gender)
    {
        return $this->setData(self::GENDER, $gender);
    }

    /**
     * Get dob
     * @return string|null
     */
    public function getDob()
    {
        return $this->_get(self::DOB);
    }

    /**
     * Set dob
     * @param string $dob
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setDob($dob)
    {
        return $this->setData(self::DOB, $dob);
    }

    /**
     * Get address1
     * @return string|null
     */
    public function getAddress1()
    {
        return $this->_get(self::ADDRESS1);
    }

    /**
     * Set address1
     * @param string $address1
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setAddress1($address1)
    {
        return $this->setData(self::ADDRESS1, $address1);
    }

    /**
     * Get address2
     * @return string|null
     */
    public function getAddress2()
    {
        return $this->_get(self::ADDRESS2);
    }

    /**
     * Set address2
     * @param string $address2
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setAddress2($address2)
    {
        return $this->setData(self::ADDRESS2, $address2);
    }

    /**
     * Get postcode
     * @return string|null
     */
    public function getPostcode()
    {
        return $this->_get(self::POSTCODE);
    }

    /**
     * Set postcode
     * @param string $postcode
     * @return \Sachin\Customer\Api\Data\AgeverificationInterface
     */
    public function setPostcode($postcode)
    {
        return $this->setData(self::POSTCODE, $postcode);
    }
}

