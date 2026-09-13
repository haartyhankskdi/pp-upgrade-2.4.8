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
use Mirasvit\Helpdesk\Api\Data\DepartmentInterface;
use Mirasvit\Helpdesk\Api\Data\DepartmentSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\DepartmentSearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\DepartmentRepositoryInterface;
use Mirasvit\Helpdesk\Model\DepartmentFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Department as DepartmentResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory as DepartmentCollectionFactory;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    private $departmentFactory;

    private $departmentResource;

    private $collectionFactory;

    private $searchResultsFactory;

    public function __construct(
        DepartmentFactory $departmentFactory,
        DepartmentResource $departmentResource,
        DepartmentCollectionFactory $collectionFactory,
        DepartmentSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->departmentFactory    = $departmentFactory;
        $this->departmentResource   = $departmentResource;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function get(int $departmentId): DepartmentInterface
    {
        $department = $this->departmentFactory->create();
        $this->departmentResource->load($department, $departmentId);

        if (!$department->getId()) {
            throw new NoSuchEntityException(__('Department with id "%1" does not exist.', $departmentId));
        }

        return $department;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): DepartmentSearchResultsInterface
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
