<?php

namespace Amasty\Reports\Controller\Adminhtml\Report\Catalog;

class Bestsellers extends ByProduct
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Reports::reports_catalog_bestsellers');
    }
}
