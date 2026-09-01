<?php

namespace Amasty\Reports\Controller\Adminhtml\Rule;

use Amasty\Reports\Controller\Adminhtml\Rule as RuleController;
use Magento\Backend\App\Action;

class NewAction extends RuleController
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        // phpcs:ignore Magento2.Legacy.ObsoleteResponse.ForwardResponseMethodFound
        $this->_forward('edit');
    }
}
