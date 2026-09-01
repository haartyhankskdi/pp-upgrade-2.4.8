<?php

declare(strict_types=1);

namespace Amasty\Reports\Controller\Adminhtml\Report\Sales;

class Quote extends Sales
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Reports::reports_sales_quote');
    }
}
