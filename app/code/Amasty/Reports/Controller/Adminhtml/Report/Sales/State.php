<?php

namespace Amasty\Reports\Controller\Adminhtml\Report\Sales;

class State extends Sales
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Reports::reports_sales_state');
    }
}
