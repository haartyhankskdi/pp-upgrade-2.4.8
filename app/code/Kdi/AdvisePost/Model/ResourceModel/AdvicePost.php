<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\AdvisePost\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AdvicePost extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('kdi_advice_post', 'entity_id');
    }
}

