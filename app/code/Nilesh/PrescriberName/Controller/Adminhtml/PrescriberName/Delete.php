<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Controller\Adminhtml\PrescriberName;

class Delete extends \Nilesh\PrescriberName\Controller\Adminhtml\PrescriberName
{

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('prescribername_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(\Nilesh\PrescriberName\Model\PrescriberName::class);
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the Prescriber Name.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['prescribername_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a Prescriber Name to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}

