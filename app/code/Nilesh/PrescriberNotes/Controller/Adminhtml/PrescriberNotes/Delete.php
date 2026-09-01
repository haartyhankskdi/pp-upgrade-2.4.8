<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Controller\Adminhtml\PrescriberNotes;

class Delete extends \Nilesh\PrescriberNotes\Controller\Adminhtml\PrescriberNotes
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
        $id = $this->getRequest()->getParam('prescribernotes_id');            
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(\Nilesh\PrescriberNotes\Model\PrescriberNotes::class);
                $model->load($id);                
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the Prescribernotes.'));
                // go to prescriber grid
                //return $resultRedirect->setPath('*/*/');
                // go to customer grid 
                return $resultRedirect->setPath('customer/index/edit', ['id' => $model->getConnectId()]);
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['prescribernotes_id' => $id]);                
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a Prescribernotes to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Nilesh_PrescriberNotes::view');
    }
}

