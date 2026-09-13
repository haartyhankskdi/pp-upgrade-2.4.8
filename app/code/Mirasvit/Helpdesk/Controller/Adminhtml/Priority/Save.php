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



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Priority;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Mirasvit\Helpdesk\Controller\Adminhtml\Priority
{
    /**
     *
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {
            $priority = $this->_initPriority();
            $priority->addData($this->prepareData($data));
            try {
                $priority->save();

                $this->messageManager->addSuccess(__('Priority was successfully saved'));

                $this->backendSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->_redirect('*/*/edit', ['id' => $priority->getId(), 'store' => $priority->getStoreId()]);
                }
                return $this->_redirect('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->backendSession->setFormData($data);
                return $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        }
        $this->messageManager->addError(__('Unable to find Priority to save'));
        return $this->_redirect('*/*/');
    }
}
