<?php

namespace Nilesh\PrescriberNotes\Controller\Adminhtml\PrescriberNotes;

use Magento\Framework\Controller\ResultFactory;

class Uploader extends \Magento\Catalog\Controller\Adminhtml\Category\Image\Upload

{
    public function execute()
    {        
        $imageUploadId = $this->getRequest()->getParam('param_name', 'prescribernotes_upload');
        try {
            $result = $this->imageUploader->saveFileToTmpDir($imageUploadId);

            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }

    /**
     * Check admin permissions for this controller
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Nilesh_PrescriberNotes::view');
    }
}