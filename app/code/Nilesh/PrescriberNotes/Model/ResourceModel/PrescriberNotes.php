<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PrescriberNotes extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('nilesh_prescribernotes_prescribernotes', 'prescribernotes_id');
    }
}

