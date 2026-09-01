<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'generalquestions_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Nilesh\GeneralQuestions\Model\GeneralQuestions::class,
            \Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions::class
        );
    }
}

