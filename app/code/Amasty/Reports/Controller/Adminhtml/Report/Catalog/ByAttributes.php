<?php

namespace Amasty\Reports\Controller\Adminhtml\Report\Catalog;

use Amasty\Reports\Controller\Adminhtml\Report as ReportController;
use Magento\Backend\Model\View\Result\Page;

class ByAttributes extends ReportController
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Reports::reports_catalog_by_attributes');
    }

    /**
     * @return Page|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->prepareResponse();

        if ($resultPage instanceof Page) {
            $resultPage->addBreadcrumb(__('Catalog'), __('Catalog'));
        }

        return $resultPage;
    }
}
