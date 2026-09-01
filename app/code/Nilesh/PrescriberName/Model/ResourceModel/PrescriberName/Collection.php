<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Model\ResourceModel\PrescriberName;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'prescribername_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Nilesh\PrescriberName\Model\PrescriberName::class,
            \Nilesh\PrescriberName\Model\ResourceModel\PrescriberName::class
        );
    }
}

