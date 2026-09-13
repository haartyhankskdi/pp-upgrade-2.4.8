<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Permission;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Mirasvit\Helpdesk\Controller\Adminhtml\Permission
{
    /**
     *
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {
            $permission = $this->_initPermission();
            $permission->addData($this->prepareData($data));
            try {
                $permission->save();

                $this->messageManager->addSuccess(__('Permission was successfully saved'));
                $this->backendSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->_redirect('*/*/edit', ['id' => $permission->getId()]);
                }
                return $this->_redirect('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->backendSession->setFormData($data);
                return $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }
        $this->messageManager->addError(__('Unable to find Permission to save'));
        return $this->_redirect('*/*/');
    }
}
