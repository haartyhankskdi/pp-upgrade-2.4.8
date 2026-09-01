<?php

namespace Amasty\Reports\Model\OptionSource;

use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class SalesRule implements ArrayInterface
{
    /**
     * @var RuleCollectionFactory
     */
    private $collectionFactory;

    public function __construct(RuleCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $options[] = [
            'value' => '',
            'label' => __('All results')
        ];
        foreach ($this->collectionFactory->create() as $rule) {
            $options[] = [
                'value' => $rule->getRuleId(),
                'label' => $rule->getName()
            ];
        }

        return $options;
    }
}
