<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GpManagement\Model\ResourceModel\GpManagement;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'gpmanagement_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Nilesh\GpManagement\Model\GpManagement::class,
            \Nilesh\GpManagement\Model\ResourceModel\GpManagement::class
        );
    }
}

