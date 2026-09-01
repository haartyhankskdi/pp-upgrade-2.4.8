<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Controller\General;

class Ajax extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
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
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        /* File Upload */
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        /* File Upload */
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
        $message = array(
            "status" => false,
            "message" => "Something went wrong"
        );
        try {
               /* 
                    For File upload system
                */
                try{
                    $target = $this->_mediaDirectory->getAbsolutePath('gq_uploads/');
                    /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                    $uploader = $this->_fileUploaderFactory->create(['fileId' => 'files']); 
                    //Since in this example the input controller name is 'profileAdd', it shoud be used here
                    /** Allowed extension types */
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'pdf', 'png', 'doc', 'docx', 'bmp']);
                    /** rename file name if already exists */
                    $uploader->setAllowRenameFiles(true);
                    /** upload file in folder "mycustomfolder" */
                    $result = $uploader->save($target);
                    // print_r($uploader);exit();
                    if ($result['file']) {
                        // print_r($result); exit();
                        $pathOfFile = $result['file'];
                        $message['status'] = true;
                        $message['message'] = $pathOfFile;
                        $message['target'] = "gq_uploads/";
                        // $this->messageManager->addSuccess(__('File has been successfully uploaded')); 
                    }else{
                        $message['status'] = false;
                        $message['message'] = "We only allow the following extensions ( jpeg, jpg, doc, pdf, png and bmp files)";
                    }
                } catch (\Exception $e) {
                    // $this->messageManager->addError("Please check uploaded file");
                    $this->logger->critical($e->getMessage());
                    $message['status'] = false;
                    $message['message'] = "Please check uploaded file";
                }
                /* 
                    For File upload system
                 */
            return $this->jsonResponse($message);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $message['status'] = false;
            $message['message'] = $e->getMessage();
            return $this->jsonResponse($message);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $message['status'] = false;
            $message['message'] = $e->getMessage();
            return $this->jsonResponse($message);
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