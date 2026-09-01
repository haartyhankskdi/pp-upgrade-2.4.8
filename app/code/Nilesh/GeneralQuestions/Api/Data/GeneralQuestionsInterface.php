<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Api\Data;

interface GeneralQuestionsInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const PRESCRIBER_TO_KNOW = 'prescriber_to_know';
    const GENDER = 'gender';
    const SUFFER_DIAGNOSED = 'suffer_diagnosed';
    const REGISTERED_GP_SURGERY = 'registered_gp_surgery';
    const GENERALQUESTIONS_ID = 'generalquestions_id';
    const OTHER_MEDICATION = 'other_medication';
    const OTHER_MEDICATION_YES = 'other_medication_yes';
    const CUSTOMER_ID = 'customer_id';
    const SUFFER_DIAGNOSED_YES = 'suffer_diagnosed_yes';
    const UPLOAD_DOCUMENTS_PRESCRIBER = 'upload_documents_prescriber';
    const HAVE_ALLERGIES = 'have_allergies';
    const REGISTERED_GP = 'registered_gp';
    const UPLOAD_DOCUMENTS_PRESCRIBER_YES = 'upload_documents_prescriber_yes';
    const HAVE_ALLERGIES_YES = 'have_allergies_yes';
    const PRESCRIBER_TO_KNOW_YES = 'prescriber_to_know_yes';
    const REGISTERED_GP_PERMISSION = 'registered_gp_permission';

    /**
     * Get generalquestions_id
     * @return string|null
     */
    public function getGeneralquestionsId();

    /**
     * Set generalquestions_id
     * @param string $generalquestionsId
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setGeneralquestionsId($generalquestionsId);

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setCustomerId($customerId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsExtensionInterface $extensionAttributes
    );

    /**
     * Get suffer_diagnosed
     * @return string|null
     */
    public function getSufferDiagnosed();

    /**
     * Set suffer_diagnosed
     * @param string $sufferDiagnosed
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setSufferDiagnosed($sufferDiagnosed);

    /**
     * Get suffer_diagnosed_yes
     * @return string|null
     */
    public function getSufferDiagnosedYes();

    /**
     * Set suffer_diagnosed_yes
     * @param string $sufferDiagnosedYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setSufferDiagnosedYes($sufferDiagnosedYes);

    /**
     * Get other_medication
     * @return string|null
     */
    public function getOtherMedication();

    /**
     * Set other_medication
     * @param string $otherMedication
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setOtherMedication($otherMedication);

    /**
     * Get other_medication_yes
     * @return string|null
     */
    public function getOtherMedicationYes();

    /**
     * Set other_medication_yes
     * @param string $otherMedicationYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setOtherMedicationYes($otherMedicationYes);

    /**
     * Get have_allergies
     * @return string|null
     */
    public function getHaveAllergies();

    /**
     * Set have_allergies
     * @param string $haveAllergies
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setHaveAllergies($haveAllergies);

    /**
     * Get have_allergies_yes
     * @return string|null
     */
    public function getHaveAllergiesYes();

    /**
     * Set have_allergies_yes
     * @param string $haveAllergiesYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setHaveAllergiesYes($haveAllergiesYes);

    /**
     * Get registered_gp
     * @return string|null
     */
    public function getRegisteredGp();

    /**
     * Set registered_gp
     * @param string $registeredGp
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setRegisteredGp($registeredGp);

    /**
     * Get registered_gp_permission
     * @return string|null
     */
    public function getRegisteredGpPermission();

    /**
     * Set registered_gp_permission
     * @param string $registeredGpPermission
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setRegisteredGpPermission($registeredGpPermission);

    /**
     * Get registered_gp_surgery
     * @return string|null
     */
    public function getRegisteredGpSurgery();

    /**
     * Set registered_gp_surgery
     * @param string $registeredGpSurgery
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setRegisteredGpSurgery($registeredGpSurgery);

    /**
     * Get upload_documents_prescriber
     * @return string|null
     */
    public function getUploadDocumentsPrescriber();

    /**
     * Set upload_documents_prescriber
     * @param string $uploadDocumentsPrescriber
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setUploadDocumentsPrescriber($uploadDocumentsPrescriber);

    /**
     * Get upload_documents_prescriber_yes
     * @return string|null
     */
    public function getUploadDocumentsPrescriberYes();

    /**
     * Set upload_documents_prescriber_yes
     * @param string $uploadDocumentsPrescriberYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setUploadDocumentsPrescriberYes($uploadDocumentsPrescriberYes);

    /**
     * Get prescriber_to_know
     * @return string|null
     */
    public function getPrescriberToKnow();

    /**
     * Set prescriber_to_know
     * @param string $prescriberToKnow
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setPrescriberToKnow($prescriberToKnow);

    /**
     * Get prescriber_to_know_yes
     * @return string|null
     */
    public function getPrescriberToKnowYes();

    /**
     * Set prescriber_to_know_yes
     * @param string $prescriberToKnowYes
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setPrescriberToKnowYes($prescriberToKnowYes);

    /**
     * Get gender
     * @return string|null
     */
    public function getGender();

    /**
     * Set gender
     * @param string $gender
     * @return \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface
     */
    public function setGender($gender);
}

