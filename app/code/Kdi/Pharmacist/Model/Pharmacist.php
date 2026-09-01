<?php

namespace Kdi\Pharmacist\Model;

use Magento\Framework\Model\AbstractModel;

class Pharmacist extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Kdi\Pharmacist\Model\ResourceModel\Pharmacist::class);
    }
}
