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
use Mirasvit\Helpdesk\Api\Data\StatusInterface;
use Mirasvit\Helpdesk\Api\Data\StatusSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\StatusSearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\StatusRepositoryInterface;
use Mirasvit\Helpdesk\Model\StatusFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Status as StatusResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Status\CollectionFactory as StatusCollectionFactory;

class StatusRepository implements StatusRepositoryInterface
{
    private $statusFactory;

    private $statusResource;

    private $collectionFactory;

    private $searchResultsFactory;

    public function __construct(
        StatusFactory $statusFactory,
        StatusResource $statusResource,
        StatusCollectionFactory $collectionFactory,
        StatusSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->statusFactory        = $statusFactory;
        $this->statusResource       = $statusResource;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(StatusInterface $status): StatusInterface
    {
        $this->statusResource->save($status);

        return $status;
    }

    public function get(int $statusId): StatusInterface
    {
        $status = $this->statusFactory->create();
        $this->statusResource->load($status, $statusId);

        if (!$status->getId()) {
            throw new NoSuchEntityException(__('Status with id "%1" does not exist.', $statusId));
        }

        return $status;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): StatusSearchResultsInterface
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
