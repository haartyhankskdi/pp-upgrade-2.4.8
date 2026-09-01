<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GpManagement\Api\Data;

interface GpManagementInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const GPMANAGEMENT_ID = 'gpmanagement_id';
    const EMAIL = 'email';
    const NAME_OF_PRACTICE = 'name_of_practice';
    const CITY = 'city';
    const TELEPHONE = 'telephone';
    const ADDRESS_LINE_TWO = 'address_line_two';
    const COUNTY = 'county';
    const POSTCODE = 'postcode';
    const ADDRESS_LINE_ONE = 'address_line_one';
    const PRACTICE_CODE = 'practice_code';
    const CREATED_DATE = 'created_date';
    const MODIFY_DATE = 'modify_date';
    const ADDITIONAL_INFO = 'additional_info';

    /**
     * Get gpmanagement_id
     * @return string|null
     */
    public function getGpmanagementId();

    /**
     * Set gpmanagement_id
     * @param string $gpmanagementId
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setGpmanagementId($gpmanagementId);

    /**
     * Get practice_code
     * @return string|null
     */
    public function getPracticeCode();

    /**
     * Set practice_code
     * @param string $practiceCode
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setPracticeCode($practiceCode);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Nilesh\GpManagement\Api\Data\GpManagementExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Nilesh\GpManagement\Api\Data\GpManagementExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Nilesh\GpManagement\Api\Data\GpManagementExtensionInterface $extensionAttributes
    );

    /**
     * Get name_of_practice
     * @return string|null
     */
    public function getNameOfPractice();

    /**
     * Set name_of_practice
     * @param string $nameOfPractice
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setNameOfPractice($nameOfPractice);

    /**
     * Get email
     * @return string|null
     */
    public function getEmail();

    /**
     * Set email
     * @param string $email
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setEmail($email);

    /**
     * Get telephone
     * @return string|null
     */
    public function getTelephone();

    /**
     * Set telephone
     * @param string $telephone
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setTelephone($telephone);

    /**
     * Get address_line_one
     * @return string|null
     */
    public function getAddressLineOne();

    /**
     * Set address_line_one
     * @param string $addressLineOne
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setAddressLineOne($addressLineOne);

    /**
     * Get address_line_two
     * @return string|null
     */
    public function getAddressLineTwo();

    /**
     * Set address_line_two
     * @param string $addressLineTwo
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setAddressLineTwo($addressLineTwo);

    /**
     * Get city
     * @return string|null
     */
    public function getCity();

    /**
     * Set city
     * @param string $city
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setCity($city);

    /**
     * Get county
     * @return string|null
     */
    public function getCounty();

    /**
     * Set county
     * @param string $county
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setCounty($county);

    /**
     * Get postcode
     * @return string|null
     */
    public function getPostcode();

    /**
     * Set postcode
     * @param string $postcode
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setPostcode($postcode);

    /**
     * Get additional_info
     * @return string|null
     */
    public function getAdditionalInfo();

    /**
     * Set additional_info
     * @param string $additionalInfo
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setAdditionalInfo($additionalInfo);

    /**
     * Get created_date
     * @return string|null
     */
    public function getCreatedDate();

    /**
     * Set created_date
     * @param string $createdDate
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setCreatedDate($createdDate);

    /**
     * Get modify_date
     * @return string|null
     */
    public function getModifyDate();

    /**
     * Set modify_date
     * @param string $modifyDate
     * @return \Nilesh\GpManagement\Api\Data\GpManagementInterface
     */
    public function setModifyDate($modifyDate);
}

