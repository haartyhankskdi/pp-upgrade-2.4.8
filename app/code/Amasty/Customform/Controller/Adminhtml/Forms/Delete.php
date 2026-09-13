<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml\Forms;

use Amasty\Customform\Api\Data\FormInterface;

class Delete extends \Amasty\Customform\Controller\Adminhtml\Form
{
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(FormInterface::ADMIN_RESOURCE_DELETE);
    }

    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('form_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->_getSession()->unsAmCustomFormData();

        if ($id) {
            try {
                // init model and delete
                $this->formRepository->deleteById($id);
                // display success message
                $this->messageManager->addSuccessMessage(__('The form has been deleted.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['form_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a form to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
