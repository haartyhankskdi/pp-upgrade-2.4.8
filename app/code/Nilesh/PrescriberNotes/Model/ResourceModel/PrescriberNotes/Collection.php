<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'prescribernotes_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Nilesh\PrescriberNotes\Model\PrescriberNotes::class,
            \Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes::class
        );
    }
}

