<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Controller\General;
use Nilesh\GeneralQuestions\Model\GeneralQuestionsFactory as GeneralQuestion;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Post extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    private $genralQuestion;
    protected $customerSession;
    protected $customerRepository;
    
    /* File Upload */
    protected $_mediaDirectory;
    protected $_fileUploaderFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        GeneralQuestion $genralQuestion,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        /* File Upload */
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        /* File Upload */
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->genralQuestion = $genralQuestion;
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {   
        /* Our Code go here */
        $data = array();
        $refferal = 'customer/general/question';
        $post = $this->getRequest()->getPostValue();

        // $id = $post[''];
        // Set data Value
        // $data['question_one'] = $post['question_one'];
        $customer_id = $this->customerSession->getCustomer()->getId();
        if (isset($post['gq_id']) && !empty($post['gq_id'])) {
            $genralQuestionM = $this->genralQuestion->create();
            $model =  $genralQuestionM->load($post['gq_id'], 'generalquestions_id');
        }else{
            $model = $this->genralQuestion->create();
            $model->setCustomerId($customer_id);
        }
        // print_r($model->getData());
        // exit($id);

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
            $checkFile = $this->getRequest()->getFiles();
            // print_r($checkFile['upload_documents_prescriber_yes']['name']); exit();
            if($post['upload_documents_prescriber'] == 1 && isset($checkFile['upload_documents_prescriber_yes']['name']) && !empty($checkFile['upload_documents_prescriber_yes']['name'])){
                /* File Uplading */
                try{
                    $target = $this->_mediaDirectory->getAbsolutePath('gq_uploads/');
                    /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                    $uploader = $this->_fileUploaderFactory->create(['fileId' => 'upload_documents_prescriber_yes']); 
                    //Since in this example the input controller name is 'profileAdd', it shoud be used here
                    /** Allowed extension types */
                    $uploader->setAllowedExtensions(['jpg', 'pdf', 'doc', 'png', 'zip', 'doc']);
                    /** rename file name if already exists */
                    $uploader->setAllowRenameFiles(true);
                    /** upload file in folder "mycustomfolder" */
                    $result = $uploader->save($target);
                    // print_r($uploader);exit();
                    if ($result['file']) {
                        // print_r($result); exit();
                        $pathOfFile = $result['file'];
                        $model->setUploadDocumentsPrescriberYes($pathOfFile);
                        if(isset($post['upload_documents_prescriber_yes_uploaded'])){
                            unlink($target.$post['upload_documents_prescriber_yes_uploaded']);
                        }
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

        // $model->setData($data);

        // $model->setQuestionOne($post['question_one']);

        /* Need to save general question too */
        $customer = $this->customerRepository->getById($customer_id);
        $customer->setCustomAttribute('general_question', "1");

        try {
            $model->save();
            $this->customerRepository->save($customer);
            $this->customerSession->getCustomer()->setData('general_question', "1");
            // success message with help of messageManager get from customer login controller
            $this->messageManager->addSuccess(
                __("Customer's general questions updated successfully")
            );


            // Setting up referral 
            if(isset($post['referer']) && !empty(trim($post['referer']))){
                $refferal =  base64_decode($post['referer']);
            }

            $this->_redirect($refferal);
            return;
            // return $this->jsonResponse('your response');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}

