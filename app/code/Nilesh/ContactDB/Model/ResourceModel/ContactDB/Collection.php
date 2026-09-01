<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\ContactDB\Model\ResourceModel\ContactDB;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'contactdb_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Nilesh\ContactDB\Model\ContactDB::class,
            \Nilesh\ContactDB\Model\ResourceModel\ContactDB::class
        );
    }
}

