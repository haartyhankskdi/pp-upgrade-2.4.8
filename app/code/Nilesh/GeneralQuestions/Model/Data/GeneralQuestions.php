<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Model\Data;

use Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface;

class GeneralQuestions extends \Magento\Framework\Api\AbstractExtensibleObject implements GeneralQuestionsInterface
{

    /**
     * Get generalquestions_id
     * @return string|null
     */
    public function getGeneralquestionsId()
    {
        return $this->_get(self::GENERALQUESTIONS_ID);
    }

    /**
     * Set generalquestions_id
     * @param string $generalquestionsId
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setGeneralquestionsId($generalquestionsId)
    {
        return $this->setData(self::GENERALQUESTIONS_ID, $generalquestionsId);
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
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * Get suffer_diagnosed
     * @return string|null
     */
    public function getSufferDiagnosed()
    {
        return $this->_get(self::SUFFER_DIAGNOSED);
    }

    /**
     * Set suffer_diagnosed
     * @param string $sufferDiagnosed
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setSufferDiagnosed($sufferDiagnosed)
    {
        return $this->setData(self::SUFFER_DIAGNOSED, $sufferDiagnosed);
    }

    /**
     * Get suffer_diagnosed_yes
     * @return string|null
     */
    public function getSufferDiagnosedYes()
    {
        return $this->_get(self::SUFFER_DIAGNOSED_YES);
    }

    /**
     * Set suffer_diagnosed_yes
     * @param string $sufferDiagnosedYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setSufferDiagnosedYes($sufferDiagnosedYes)
    {
        return $this->setData(self::SUFFER_DIAGNOSED_YES, $sufferDiagnosedYes);
    }

    /**
     * Get other_medication
     * @return string|null
     */
    public function getOtherMedication()
    {
        return $this->_get(self::OTHER_MEDICATION);
    }

    /**
     * Set other_medication
     * @param string $otherMedication
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setOtherMedication($otherMedication)
    {
        return $this->setData(self::OTHER_MEDICATION, $otherMedication);
    }

    /**
     * Get other_medication_yes
     * @return string|null
     */
    public function getOtherMedicationYes()
    {
        return $this->_get(self::OTHER_MEDICATION_YES);
    }

    /**
     * Set other_medication_yes
     * @param string $otherMedicationYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setOtherMedicationYes($otherMedicationYes)
    {
        return $this->setData(self::OTHER_MEDICATION_YES, $otherMedicationYes);
    }

    /**
     * Get have_allergies
     * @return string|null
     */
    public function getHaveAllergies()
    {
        return $this->_get(self::HAVE_ALLERGIES);
    }

    /**
     * Set have_allergies
     * @param string $haveAllergies
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setHaveAllergies($haveAllergies)
    {
        return $this->setData(self::HAVE_ALLERGIES, $haveAllergies);
    }

    /**
     * Get have_allergies_yes
     * @return string|null
     */
    public function getHaveAllergiesYes()
    {
        return $this->_get(self::HAVE_ALLERGIES_YES);
    }

    /**
     * Set have_allergies_yes
     * @param string $haveAllergiesYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setHaveAllergiesYes($haveAllergiesYes)
    {
        return $this->setData(self::HAVE_ALLERGIES_YES, $haveAllergiesYes);
    }

    /**
     * Get registered_gp
     * @return string|null
     */
    public function getRegisteredGp()
    {
        return $this->_get(self::REGISTERED_GP);
    }

    /**
     * Set registered_gp
     * @param string $registeredGp
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setRegisteredGp($registeredGp)
    {
        return $this->setData(self::REGISTERED_GP, $registeredGp);
    }

    /**
     * Get registered_gp_permission
     * @return string|null
     */
    public function getRegisteredGpPermission()
    {
        return $this->_get(self::REGISTERED_GP_PERMISSION);
    }

    /**
     * Set registered_gp_permission
     * @param string $registeredGpPermission
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setRegisteredGpPermission($registeredGpPermission)
    {
        return $this->setData(self::REGISTERED_GP_PERMISSION, $registeredGpPermission);
    }

    /**
     * Get registered_gp_surgery
     * @return string|null
     */
    public function getRegisteredGpSurgery()
    {
        return $this->_get(self::REGISTERED_GP_SURGERY);
    }

    /**
     * Set registered_gp_surgery
     * @param string $registeredGpSurgery
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setRegisteredGpSurgery($registeredGpSurgery)
    {
        return $this->setData(self::REGISTERED_GP_SURGERY, $registeredGpSurgery);
    }

    /**
     * Get upload_documents_prescriber
     * @return string|null
     */
    public function getUploadDocumentsPrescriber()
    {
        return $this->_get(self::UPLOAD_DOCUMENTS_PRESCRIBER);
    }

    /**
     * Set upload_documents_prescriber
     * @param string $uploadDocumentsPrescriber
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setUploadDocumentsPrescriber($uploadDocumentsPrescriber)
    {
        return $this->setData(self::UPLOAD_DOCUMENTS_PRESCRIBER, $uploadDocumentsPrescriber);
    }

    /**
     * Get upload_documents_prescriber_yes
     * @return string|null
     */
    public function getUploadDocumentsPrescriberYes()
    {
        return $this->_get(self::UPLOAD_DOCUMENTS_PRESCRIBER_YES);
    }

    /**
     * Set upload_documents_prescriber_yes
     * @param string $uploadDocumentsPrescriberYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setUploadDocumentsPrescriberYes($uploadDocumentsPrescriberYes)
    {
        return $this->setData(self::UPLOAD_DOCUMENTS_PRESCRIBER_YES, $uploadDocumentsPrescriberYes);
    }

    /**
     * Get prescriber_to_know
     * @return string|null
     */
    public function getPrescriberToKnow()
    {
        return $this->_get(self::PRESCRIBER_TO_KNOW);
    }

    /**
     * Set prescriber_to_know
     * @param string $prescriberToKnow
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setPrescriberToKnow($prescriberToKnow)
    {
        return $this->setData(self::PRESCRIBER_TO_KNOW, $prescriberToKnow);
    }

    /**
     * Get prescriber_to_know_yes
     * @return string|null
     */
    public function getPrescriberToKnowYes()
    {
        return $this->_get(self::PRESCRIBER_TO_KNOW_YES);
    }

    /**
     * Set prescriber_to_know_yes
     * @param string $prescriberToKnowYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setPrescriberToKnowYes($prescriberToKnowYes)
    {
        return $this->setData(self::PRESCRIBER_TO_KNOW_YES, $prescriberToKnowYes);
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
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setGender($gender)
    {
        return $this->setData(self::GENDER, $gender);
    }
}

