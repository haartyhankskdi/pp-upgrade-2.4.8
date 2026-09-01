<?php

namespace Nilesh\PrescriberName\Ui\Component\DataProvider;

class PrescriberNameFilter implements \Magento\Ui\DataProvider\AddFilterToCollectionInterface
{
    public function addFilter(\Magento\Framework\Data\Collection $collection, $field, $condition = null)
    {
        if (isset($condition['eq'])) {
            $collection->addFieldToFilter($field, $condition);
        }
    }
}