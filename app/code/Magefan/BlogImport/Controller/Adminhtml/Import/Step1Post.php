<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Controller\Adminhtml\Import;

/**
 * Blog prepare csv import controller
 */
class Step1Post extends \Magento\Backend\App\Action
{
    /**
     * Prepare wordpress import
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $type = $this->getRequest()->getParam('type');

        $imageField = 'file';
        $fileSystem = $this->_objectManager->create(\Magento\Framework\Filesystem::class);
        $mediaDirectory = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        try {
            $uploader = $this->_objectManager->create(\Magento\Framework\File\UploaderFactory::class);
            $uploader = $uploader->create(['fileId' => $imageField]);
            $uploader->setAllowedExtensions(['csv', 'xml']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowCreateFolders(true);
            $result = $uploader->save(
                $mediaDirectory->getAbsolutePath('magefan_blog/tmp')
            );

            $this->_getSession()->setData(
                'import_csv_file',
                'magefan_blog/tmp' . $result['file']
            );

            /* Try to get rows */
            $this->_objectManager->create(\Magefan\BlogImport\Model\Csv::class)->getRows();

        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $this->_redirect('*/*/' . $type);
        }

        $this->messageManager->addSuccess(__('File uploaded successfully.'));
        return $this->_redirect(
            $this->getRequest()->getParam('next') ?: '*/*/step2',
            [
                'store_id' => $this->getRequest()->getParam('store_id'),
                'type' => $type
            ]
        );
    }

    /**
     * Check is allowed access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magefan_BlogImport::import');
    }
}
