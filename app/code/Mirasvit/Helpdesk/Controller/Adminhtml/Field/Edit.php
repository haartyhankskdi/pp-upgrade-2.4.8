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



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Field;

use Magento\Framework\Controller\ResultFactory;

class Edit extends \Mirasvit\Helpdesk\Controller\Adminhtml\Field
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $field = $this->_initField();

        if ($field->getId()) {
            $this->_initAction();
            $resultPage->getConfig()->getTitle()->prepend(__("Edit Field '%1'", $field->getName()));
            $resultPage->addBreadcrumb(
                __('Custom Fields'),
                __('Custom Fields'),
                $this->getUrl('*/*/')
            );
            $resultPage->addBreadcrumb(
                __('Edit Field '),
                __('Edit Field ')
            );

            $resultPage->getLayout()
                ->getBlock('head')
                ;

            return $resultPage;
        } else {
            $this->messageManager->addError(__('The Field does not exist.'));
            return $this->_redirect('*/*/');
        }
    }
}
