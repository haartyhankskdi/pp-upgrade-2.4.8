<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel;

use Amasty\Reports\Api\Data\NotificationInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Notification extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(NotificationInterface::TABLE_NAME, NotificationInterface::ENTITY_ID);
    }
}
