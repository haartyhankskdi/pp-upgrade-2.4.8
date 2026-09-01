<?php

declare(strict_types=1);

namespace Amasty\Reports\Controller\Adminhtml\Notification;

use Amasty\Reports\Controller\Adminhtml\Notification;

class NewAction extends Notification
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
