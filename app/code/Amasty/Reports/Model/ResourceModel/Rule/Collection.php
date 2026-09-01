<?php

namespace Amasty\Reports\Model\ResourceModel\Rule;

use Amasty\Reports\Api\Data\RuleInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_setIdFieldName(RuleInterface::ENTITY_ID);
        $this->_init(
            \Amasty\Reports\Model\Rule::class,
            \Amasty\Reports\Model\ResourceModel\Rule::class
        );
    }
}
