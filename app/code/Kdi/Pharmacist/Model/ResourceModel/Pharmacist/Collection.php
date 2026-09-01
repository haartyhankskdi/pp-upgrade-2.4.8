<?php

namespace Kdi\Pharmacist\Model\ResourceModel\Pharmacist;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Kdi\Pharmacist\Model\Pharmacist as Model;
use Kdi\Pharmacist\Model\ResourceModel\Pharmacist as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
