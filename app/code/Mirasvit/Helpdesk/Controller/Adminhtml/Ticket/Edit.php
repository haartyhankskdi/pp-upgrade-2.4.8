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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Ticket;

use Magento\Framework\Controller\ResultFactory;
use Symfony\Component\Finder\Exception\AccessDeniedException;

class Edit extends \Mirasvit\Helpdesk\Controller\Adminhtml\Ticket
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $ticket = $this->_initTicket();
        } catch (AccessDeniedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('*/*/');

            return $resultRedirect;
        }
        if ($ticket->getId()) {
            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

            $resultPage->getConfig()->getTitle()->prepend(
                $this->escaper->escapeHtml('[#'.$ticket->getCode().'] '.$ticket->getSubject())
            );
            $this->_initAction();

            $user = $this->helpdeskUser->getHelpdeskUser();
            $collection = $this->desktopNotificationHelper->getNotificationsByTicket($ticket);
            foreach ($collection as $notification) {
                $notification->addReadByUserId($user->getId());
                $notification->save();
            }

            $this->_addBreadcrumb(__('Tickets'), __('Tickets'), $this->getUrl('*/*/'));
            $this->_addBreadcrumb(__('Edit Ticket '), __('Edit Ticket '));

            return $resultPage;
        } else {
            $this->messageManager->addErrorMessage(__('The ticket does not exist.'));

            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('*/*/');

            return $resultRedirect;
        }
    }
}
