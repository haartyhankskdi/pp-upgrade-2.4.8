<?php

declare(strict_types=1);

namespace Amasty\Reports\Ui\DataProvider\Listing\Customers\Returning;

use Magento\Framework\Api\Search\SearchResultInterface;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var string[]
     */
    private $customColumns = ['new_customers', 'returning_customers', 'percent'];

    /**
     * @var array
     */
    private $havingColumns = [];

    /**
     * @var array
     */
    private $havingFilters = [];

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if (in_array($filter->getField(), $this->customColumns)) {
            $this->prepareHavingColumns();
            $this->havingFilters[] = $filter;
            return;
        }

        parent::addFilter($filter);
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
                sprintf('%s %s "%s"', $fieldExpr, $operations[$filter->getConditionType()], $filter->getValue())
            );
        }

        return parent::searchResultToOutput($searchResult);
    }

    private function prepareHavingColumns(): void
    {
        if (!$this->havingColumns) {
            $config = $this->getConfigData();
            if ($config && isset($config['selectProvider'])) {
                $this->havingColumns['new_customers'] = $config['selectProvider']->getNewCustomersQuery();
                $this->havingColumns['returning_customers'] = $config['selectProvider']->getReturningCustomersSelect();
                $this->havingColumns['percent'] = $config['selectProvider']->getPercentSelect();
            }
        }
    }
}
