<?php

namespace Amasty\Reports\Controller\Adminhtml\Report\Sales;

use Amasty\Reports\Controller\Adminhtml\Report as ReportController;
use Magento\Backend\Model\View\Result\Page;

class Sales extends ReportController
{
    /**
     * @return Page|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->prepareResponse();

        if ($resultPage instanceof Page) {
            $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        }

        return $resultPage;
    }
}
