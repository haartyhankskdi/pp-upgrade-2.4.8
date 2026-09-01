<?php

namespace Amasty\Reports\Controller\Adminhtml\Rule;

use Amasty\Reports\Controller\Adminhtml\Rule as RuleController;
use Magento\Framework\Controller\ResultFactory;

class Index extends RuleController
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__('Advanced Reports Rules'), __('Advanced Reports Rules'));
        $resultPage->getConfig()->getTitle()->prepend(__('Advanced Reports Rules'));

        return $resultPage;
    }
}
