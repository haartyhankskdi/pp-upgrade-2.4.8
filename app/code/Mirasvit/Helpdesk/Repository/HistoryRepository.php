<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Repository;

use Magento\Framework\Api\SearchCriteriaInterface;
use Mirasvit\Helpdesk\Api\Data\HistorySearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\HistorySearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\HistoryRepositoryInterface;
use Mirasvit\Helpdesk\Model\ResourceModel\History\CollectionFactory;

class HistoryRepository implements HistoryRepositoryInterface
{
    private $collectionFactory;

    private $searchResultsFactory;

    public function __construct(
        CollectionFactory $collectionFactory,
        HistorySearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): HistorySearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $fields     = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() ?? [] as $filter) {
                $condition    = $filter->getConditionType() ?: 'eq';
                $fields[]     = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $direction = ($sortOrder->getDirection() === \Magento\Framework\Api\SortOrder::SORT_DESC)
                    ? 'DESC'
                    : 'ASC';
                $collection->setOrder($sortOrder->getField(), $direction);
            }
        } else {
            $collection->setOrder('created_at', 'DESC');
        }

        $totalCount = $collection->getSize();

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($totalCount);

        return $searchResults;
    }
}
