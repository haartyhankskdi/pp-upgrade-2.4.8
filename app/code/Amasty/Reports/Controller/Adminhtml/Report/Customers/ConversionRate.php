<?php

namespace Amasty\Reports\Controller\Adminhtml\Report\Customers;

use Amasty\Reports\Controller\Adminhtml\Report as ReportController;
use Magento\Backend\Model\View\Result\Page;

class ConversionRate extends ReportController
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Reports::reports_customers_conversion_rate');
    }

    /**
     * @return Page|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->prepareResponse();

        if ($resultPage instanceof Page) {
            $resultPage->addBreadcrumb(__('Conversion Rate'), __('Conversion Rate'));
        }

        return $resultPage;
    }
}
