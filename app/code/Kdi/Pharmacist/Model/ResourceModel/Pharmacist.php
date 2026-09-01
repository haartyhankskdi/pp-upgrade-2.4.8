<?php

namespace Kdi\Pharmacist\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Pharmacist extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('pharmacist', 'entity_id');  // Table name and Primary key
    }
}
