<?php

namespace Kdi\Pharmacist\Controller\Adminhtml\Pharmacist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem\DirectoryList;

class Upload extends Action
{
    protected $jsonFactory;
    protected $uploaderFactory;
    protected $filesystem;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'image']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $result = $uploader->save($mediaDirectory->getAbsolutePath('pharmacist/images'));

            if (!$result) {
                throw new \Exception('Image upload failed.');
            }

            $result['url'] = $this->_url->getBaseUrl() . 'pub/media/pharmacist/images/' . $result['file'];

            return $this->jsonFactory->create()->setData($result);
        } catch (\Exception $e) {
            return $this->jsonFactory->create()->setData(['error' => $e->getMessage(), 'errorcode' => $e->getCode()]);
        }
    }
}
