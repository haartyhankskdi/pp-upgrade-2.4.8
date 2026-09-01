<?php

namespace Amasty\Reports\Ui\DataProvider\Listing\Sales\CartRule;

use Magento\Framework\Api\Search\SearchResultInterface;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var array
     */
    private $havingColumns = [
        'total_orders',
        'total_items',
        'subtotal',
        'tax',
        'shipping',
        'discounts',
        'total',
        'invoiced',
        'refunded'
    ];

    /**
     * @var array
     */
    private $havingFilters = [];

    /**
     * @param \Magento\Framework\Api\Filter $filter
     *
     * @return mixed|void
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'period') {
            $filter->setField(new \Zend_Db_Expr('CONCAT(\',\',applied_rule_ids,\',\')'));
            $filter->setConditionType('like');
            $filter->setValue('%,' . $filter->getValue() . ',%');
        } elseif (in_array($filter->getField(), $this->havingColumns)) {
            $this->havingFilters[] = $filter;
            return $this;
        }

        parent::addFilter($filter);

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $operations = [
            'gteq' => '>=',
            'lteq' => '<=',
            'like' => 'like'
        ];

        foreach ($this->havingFilters as $filter) {
            $searchResult->getSelect()->having(
                $filter->getField() . ' ' . $operations[$filter->getConditionType()] . ' "' . $filter->getValue() . '"'
            );
        }

        return parent::searchResultToOutput($searchResult);
    }
}
