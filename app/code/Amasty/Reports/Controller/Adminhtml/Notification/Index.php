<?php

declare(strict_types=1);

namespace Amasty\Reports\Controller\Adminhtml\Notification;

use Amasty\Reports\Controller\Adminhtml\Notification;
use Magento\Framework\Controller\ResultFactory;

class Index extends Notification
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__('Report Notifications'), __('Report Notifications'));
        $resultPage->getConfig()->getTitle()->prepend(__('Report Notifications'));

        return $resultPage;
    }
}
