<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Controller\General;

class AjaxUnlink extends \Magento\Framework\App\Action\Action
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
        $post = $this->getRequest()->getPostValue();
        $message = array(
            "status" => false,
            "message" => "Something went wrong"
        );
        try {
            $target = $this->_mediaDirectory->getAbsolutePath('gq_uploads/');
            \unlink($target.$post['rm_upload']);

            $message['status'] = true;
            $message['message'] = "File removed successfully";
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