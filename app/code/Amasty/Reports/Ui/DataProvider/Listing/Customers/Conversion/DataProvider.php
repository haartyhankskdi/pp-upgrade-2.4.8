<?php

namespace Amasty\Reports\Ui\DataProvider\Listing\Customers\Conversion;

use Magento\Framework\Api\Search\SearchResultInterface;
use Amasty\Reports\Model\ResourceModel\Customers\Conversion\Grid\CollectionFactory;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    public const FILTER_VISITORS = 'visitors';
    public const FILTER_ORDERS = 'orders';
    public const FILTER_CONVERSION = 'conversion';

    /**
     * @var array
     */
    private $havingColumns = [
        'visitors' => 'COUNT(DISTINCT main_table.session_id)',
        'orders' => 'COUNT(DISTINCT orderTable.entity_id)',
        'conversion' => 'ROUND(COUNT(DISTINCT orderTable.entity_id) / COUNT(DISTINCT main_table.session_id) * 100)'
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
        } elseif (in_array($filter->getField(), array_keys($this->havingColumns))) {
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
            $fieldExpr = $this->havingColumns[$filter->getField()];
            $searchResult->getSelect()->having(
                $fieldExpr . ' ' . $operations[$filter->getConditionType()] . ' "' . $filter->getValue() . '"'
            );
        }

        return parent::searchResultToOutput($searchResult);
    }

    /**
     * @return array
     */
    public function getData()
    {
        $result = parent::getData();

        foreach ($result['items'] as &$orderItem) {
            $orderItem['conversion'] = round((float) $orderItem['conversion']) . '%';
        }

        return $result;
    }
}
