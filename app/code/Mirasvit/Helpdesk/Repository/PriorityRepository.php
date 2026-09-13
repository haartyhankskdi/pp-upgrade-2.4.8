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
use Magento\Framework\Exception\NoSuchEntityException;
use Mirasvit\Helpdesk\Api\Data\PriorityInterface;
use Mirasvit\Helpdesk\Api\Data\PrioritySearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\PrioritySearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\PriorityRepositoryInterface;
use Mirasvit\Helpdesk\Model\PriorityFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Priority as PriorityResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Priority\CollectionFactory as PriorityCollectionFactory;

class PriorityRepository implements PriorityRepositoryInterface
{
    private $priorityFactory;

    private $priorityResource;

    private $collectionFactory;

    private $searchResultsFactory;

    public function __construct(
        PriorityFactory $priorityFactory,
        PriorityResource $priorityResource,
        PriorityCollectionFactory $collectionFactory,
        PrioritySearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->priorityFactory      = $priorityFactory;
        $this->priorityResource     = $priorityResource;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(PriorityInterface $priority): PriorityInterface
    {
        $this->priorityResource->save($priority);

        return $priority;
    }

    public function get(int $priorityId): PriorityInterface
    {
        $priority = $this->priorityFactory->create();
        $this->priorityResource->load($priority, $priorityId);

        if (!$priority->getId()) {
            throw new NoSuchEntityException(__('Priority with id "%1" does not exist.', $priorityId));
        }

        return $priority;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): PrioritySearchResultsInterface
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
            $collection->setOrder('sort_order', 'ASC');
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
