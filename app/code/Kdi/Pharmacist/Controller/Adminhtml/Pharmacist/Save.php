<?php

namespace Kdi\Pharmacist\Controller\Adminhtml\Pharmacist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context; // Use the correct context for admin
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Kdi\Pharmacist\Model\PharmacistFactory;

class Save extends Action
{
    protected $uploaderFactory;
    protected $directoryList;
    protected $pharmacistFactory;

    public function __construct(
        Context $context, // Use Backend context
        UploaderFactory $uploaderFactory,
        DirectoryList $directoryList,
        PharmacistFactory $pharmacistFactory
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->directoryList = $directoryList;
        $this->pharmacistFactory = $pharmacistFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        // Check if file is uploaded
        if (isset($_FILES['profile']) && isset($_FILES['profile']['name']) && $_FILES['profile']['name']) {
            try {
                // Use uploader factory to handle file upload
                $uploader = $this->uploaderFactory->create(['fileId' => 'profile']);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']); // Allowed extensions
                $uploader->setAllowRenameFiles(true); // Auto-rename if a file with same name exists
                $uploader->setFilesDispersion(false); // Avoid dispersed file structure

                // Media directory path for storing the uploaded files
                $mediaDirectory = '/pub/media/pharmacist/';

                // Save file to the target directory
                $result = $uploader->save($mediaDirectory);

                if ($result) {
                    $data['image'] = 'pharmacist/' . $result['file']; // Save file path to database
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Image upload failed: %1', $e->getMessage()));
            }
        }

        if ($data) {
            try {
                $pharmacist = $this->pharmacistFactory->create();
                if (isset($data['entity_id']) && !empty($data['entity_id'])) {
                    $pharmacist->load($data['entity_id']);
                }

                $pharmacist->setData($data);
                $pharmacist->save();
                $this->messageManager->addSuccessMessage(__('Pharmacist saved successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error saving pharmacist: %1', $e->getMessage()));
            }
        }

        return $this->_redirect('*/*/');
    }


    // Override form key validation to disable it for this action
    protected function _validateFormKey()
    {
        return true; // Disable form key validation
    }
    
    
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Kdi_Pharmacist::pharmacist');
    }

}
