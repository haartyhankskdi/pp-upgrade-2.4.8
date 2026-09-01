<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GpManagement\Model\Data;

use Nilesh\GpManagement\Api\Data\GpManagementInterface;

class GpManagement extends \Magento\Framework\Api\AbstractExtensibleObject implements GpManagementInterface
{

    /**
     * Get gpmanagement_id
     * @return string|null
     */
    public function getGpmanagementId()
    {
        return $this->_get(self::GPMANAGEMENT_ID);
    }

    /**
     * Set gpmanagement_id
     * @param string $gpmanagementId
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setGpmanagementId($gpmanagementId)
    {
        return $this->setData(self::GPMANAGEMENT_ID, $gpmanagementId);
    }

    /**
     * Get practice_code
     * @return string|null
     */
    public function getPracticeCode()
    {
        return $this->_get(self::PRACTICE_CODE);
    }

    /**
     * Set practice_code
     * @param string $practiceCode
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setPracticeCode($practiceCode)
    {
        return $this->setData(self::PRACTICE_CODE, $practiceCode);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Nilesh\GpManagement\Api\Data\GpManagementExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Nilesh\GpManagement\Api\Data\GpManagementExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Nilesh\GpManagement\Api\Data\GpManagementExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get name_of_practice
     * @return string|null
     */
    public function getNameOfPractice()
    {
        return $this->_get(self::NAME_OF_PRACTICE);
    }

    /**
     * Set name_of_practice
     * @param string $nameOfPractice
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setNameOfPractice($nameOfPractice)
    {
        return $this->setData(self::NAME_OF_PRACTICE, $nameOfPractice);
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
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Get telephone
     * @return string|null
     */
    public function getTelephone()
    {
        return $this->_get(self::TELEPHONE);
    }

    /**
     * Set telephone
     * @param string $telephone
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setTelephone($telephone)
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }

    /**
     * Get address_line_one
     * @return string|null
     */
    public function getAddressLineOne()
    {
        return $this->_get(self::ADDRESS_LINE_ONE);
    }

    /**
     * Set address_line_one
     * @param string $addressLineOne
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setAddressLineOne($addressLineOne)
    {
        return $this->setData(self::ADDRESS_LINE_ONE, $addressLineOne);
    }

    /**
     * Get address_line_two
     * @return string|null
     */
    public function getAddressLineTwo()
    {
        return $this->_get(self::ADDRESS_LINE_TWO);
    }

    /**
     * Set address_line_two
     * @param string $addressLineTwo
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setAddressLineTwo($addressLineTwo)
    {
        return $this->setData(self::ADDRESS_LINE_TWO, $addressLineTwo);
    }

    /**
     * Get city
     * @return string|null
     */
    public function getCity()
    {
        return $this->_get(self::CITY);
    }

    /**
     * Set city
     * @param string $city
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setCity($city)
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * Get county
     * @return string|null
     */
    public function getCounty()
    {
        return $this->_get(self::COUNTY);
    }

    /**
     * Set county
     * @param string $county
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setCounty($county)
    {
        return $this->setData(self::COUNTY, $county);
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
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setPostcode($postcode)
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    /**
     * Get additional_info
     * @return string|null
     */
    public function getAdditionalInfo()
    {
        return $this->_get(self::ADDITIONAL_INFO);
    }

    /**
     * Set additional_info
     * @param string $additionalInfo
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setAdditionalInfo($additionalInfo)
    {
        return $this->setData(self::ADDITIONAL_INFO, $additionalInfo);
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
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setCreatedDate($createdDate)
    {
        return $this->setData(self::CREATED_DATE, $createdDate);
    }

    /**
     * Get modify_date
     * @return string|null
     */
    public function getModifyDate()
    {
        return $this->_get(self::MODIFY_DATE);
    }

    /**
     * Set modify_date
     * @param string $modifyDate
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setModifyDate($modifyDate)
    {
        return $this->setData(self::MODIFY_DATE, $modifyDate);
    }
}

