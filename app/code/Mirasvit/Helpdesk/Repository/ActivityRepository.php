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
use Magento\Framework\EntityManager\EntityManager;
use Mirasvit\Helpdesk\Api\Data\ActivityInterface;
use Mirasvit\Helpdesk\Api\Data\ActivityInterfaceFactory;
use Mirasvit\Helpdesk\Api\Data\ActivitySearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\ActivitySearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\ActivityRepositoryInterface;
use Mirasvit\Helpdesk\Model\ResourceModel\Activity\CollectionFactory;

class ActivityRepository implements ActivityRepositoryInterface
{
    private $entityManager;

    private $collectionFactory;

    private $factory;

    private $searchResultsFactory;

    public function __construct(
        EntityManager $entityManager,
        CollectionFactory $collectionFactory,
        ActivityInterfaceFactory $hostFactory,
        ActivitySearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->entityManager        = $entityManager;
        $this->collectionFactory    = $collectionFactory;
        $this->factory              = $hostFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\ResourceModel\Activity\Collection|ActivityInterface[]
     */
    public function getCollection()
    {
        return $this->collectionFactory->create();
    }

    /**
     * @return ActivityInterface
     */
    public function create()
    {
        return $this->factory->create();
    }

    /**
     * @param int $id
     * @return ActivityInterface|false
     */
    public function get($id)
    {
        $host = $this->create();
        $host = $this->entityManager->load($host, $id);

        if (!$host->getId()) {
            return false;
        }

        return $host;
    }

    /**
     * @param ActivityInterface $activity
     * @return bool
     */
    public function delete(ActivityInterface $activity)
    {
        $this->entityManager->delete($activity);

        return true;
    }

    /**
     * @param ActivityInterface $activity
     * @return ActivityInterface
     */
    public function save(ActivityInterface $activity)
    {
        return $this->entityManager->save($activity);
    }

    public function getList(SearchCriteriaInterface $searchCriteria): ActivitySearchResultsInterface
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
