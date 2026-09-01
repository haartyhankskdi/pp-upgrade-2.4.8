<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Observer\Frontend\Customer;

use Nilesh\GeneralQuestions\Model\GeneralQuestionsFactory as GeneralQuestion;

class RegisterSuccess implements \Magento\Framework\Event\ObserverInterface
{
    private $_request;
    private $genralQuestion;
    /* File Upload */
    protected $_mediaDirectory;
    protected $_fileUploaderFactory;

    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,

        \Magento\Framework\App\RequestInterface $request,
        GeneralQuestion $genralQuestion
    )
    {
        /* File Upload */
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        /* File Upload */
        $this->_request = $request;
        $this->genralQuestion = $genralQuestion;
    }
    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        //Your observer code
        $post = $this->_request->getPostValue(); 
        // $enable = $this->_request->getParam('general_question');
        $enable = $post['general_question'];

        // echo $enable; exit();
        $id = $observer->getEvent()->getCustomer()->getId();

        if( $enable == 1 ){
            $model = $this->genralQuestion->create();
            // Insert Value come here
            // $model->setQuestionOne($post['question_one']);

            /* General question basic logic start */
            $model->setSufferDiagnosed($post['suffer_diagnosed']);
            if($post['suffer_diagnosed'] == 1){
                $model->setSufferDiagnosedYes($post['suffer_diagnosed_yes']);
            }
            
            $model->setOtherMedication($post['other_medication']);
            if($post['other_medication'] == 1){
                $model->setOtherMedicationYes($post['other_medication_yes']);
            }
            
            $model->setHaveAllergies($post['have_allergies']);
            if($post['have_allergies'] == 1){
                $model->setHaveAllergiesYes($post['have_allergies_yes']);
            }
            
            $model->setRegisteredGp($post['registered_gp']);
            if($post['registered_gp'] == 1){
                $model->setRegisteredGpPermission($post['registered_gp_permission']);
                if($post['registered_gp_permission'] == 1){
                    $model->setRegisteredGpSurgery($post['registered_gp_surgery']);
                }
            }
            
            $model->setUploadDocumentsPrescriber($post['upload_documents_prescriber']);
            if($post['upload_documents_prescriber'] == 1){
                 /* File Uplading */
                try{
                    $target = $this->_mediaDirectory->getAbsolutePath('gq_uploads/');
                    /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                    $uploader = $this->_fileUploaderFactory->create(['fileId' => 'upload_documents_prescriber_yes']); //Since in this example the input controller name is 'profileAdd', it shoud be used here
                    /** Allowed extension types */
                    $uploader->setAllowedExtensions(['jpg', 'pdf', 'doc', 'png', 'zip', 'doc']);
                    /** rename file name if already exists */
                    $uploader->setAllowRenameFiles(true);
                    /** upload file in folder "mycustomfolder" */
                    $result = $uploader->save($target);
                    if ($result['file']) {
                        // print_r($result); exit();
                        $pathOfFile = $result['file'];
                        $model->setUploadDocumentsPrescriberYes($pathOfFile);
                        // $this->messageManager->addSuccess(__('File has been successfully uploaded')); 
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addError("Please check uploaded file");
                    $this->logger->critical($e->getMessage());
                    $this->_redirect($refferal);
                    return;
                }
                /* 
                    For File upload system
                 */
            }
            
            $model->setPrescriberToKnow($post['prescriber_to_know']);
            if($post['prescriber_to_know'] == 1){
                $model->setPrescriberToKnowYes($post['prescriber_to_know_yes']);
            }

            // $model->setGender($post['gender']);
            /* General question basic logic end */
            // Insert param end here
            $model->setCustomerId($id);
            // $model->setData($data);
            $model->save();
        }
    }
}

