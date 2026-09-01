<?php

namespace Nilesh\PrescriberName\Ui\Component\DataProvider;

class PrescriberNameField implements \Magento\Ui\DataProvider\AddFieldToCollectionInterface
{
    public function addField(\Magento\Framework\Data\Collection $collection, $field, $alias = null) 
    {
        $joinTable = $this->getTable('sales_order');
        $collection->joinField($joinTable, 'main_table.entity_id='.$joinTable.'.entity_id', ['prescriber_name']); 
    }
}