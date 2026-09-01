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
use Mirasvit\Helpdesk\Model\Config as Config;

class Restore extends \Mirasvit\Helpdesk\Controller\Adminhtml\Ticket
{
    /**
     * Restore ticket from archive
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $status = $this->statusFactory->create()->loadByCode(Config::STATUS_OPEN);

                $ticket = $this->ticketFactory->create()->load($this->getRequest()->getParam('id'));
                $ticket
                    ->setStatusId($status->getId())
                    ->setFolder(Config::FOLDER_INBOX)
                    ->save();

                $this->messageManager->addSuccessMessage(
                    __('Ticket was moved to the Inbox')
                );
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $resultPage->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id'), ]);
                return $resultPage;
            }
        }
        $resultPage->setRefererUrl();
        return $resultPage;
    }
}
