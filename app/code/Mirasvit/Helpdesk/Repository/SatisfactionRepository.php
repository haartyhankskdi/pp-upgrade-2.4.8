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
use Magento\Framework\Exception\StateException;
use Mirasvit\Helpdesk\Api\Data\SatisfactionInterface;
use Mirasvit\Helpdesk\Api\Data\SatisfactionSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\SatisfactionSearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\SatisfactionRepositoryInterface;
use Mirasvit\Helpdesk\Model\Message;
use Mirasvit\Helpdesk\Model\Satisfaction;
use Mirasvit\Helpdesk\Model\SatisfactionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction as SatisfactionResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction\CollectionFactory;

class SatisfactionRepository implements SatisfactionRepositoryInterface
{
    /**
     * @var Satisfaction[]
     */
    private $instances = [];

    private $objectFactory;

    private $satisfactionCollectionFactory;

    private $satisfactionResource;

    private $searchResultsFactory;

    public function __construct(
        SatisfactionFactory $satisfactionFactory,
        SatisfactionResource $satisfactionResource,
        CollectionFactory $satisfactionCollectionFactory,
        SatisfactionSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->objectFactory                 = $satisfactionFactory;
        $this->satisfactionResource          = $satisfactionResource;
        $this->searchResultsFactory          = $searchResultsFactory;
        $this->satisfactionCollectionFactory = $satisfactionCollectionFactory;
    }

    /**
     * @param Satisfaction $satisfaction
     * @return Satisfaction
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save($satisfaction)
    {
        $this->satisfactionResource->save($satisfaction);

        return $satisfaction;
    }

    public function get(int $satisfactionId): SatisfactionInterface
    {
        if (!isset($this->instances[$satisfactionId])) {
            $satisfaction = $this->objectFactory->create();
            $this->satisfactionResource->load($satisfaction, $satisfactionId);

            if (!$satisfaction->getId()) {
                throw new NoSuchEntityException(__('Satisfaction with id "%1" does not exist.', $satisfactionId));
            }

            $this->instances[$satisfactionId] = $satisfaction;
        }

        return $this->instances[$satisfactionId];
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SatisfactionSearchResultsInterface
    {
        $collection = $this->satisfactionCollectionFactory->create();

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

    /**
     * @param Satisfaction $satisfaction
     * @return bool
     * @throws StateException
     */
    public function delete($satisfaction)
    {
        try {
            $id = $satisfaction->getId();

            $this->satisfactionResource->delete($satisfaction);
        } catch (\Exception $e) {
            throw new StateException(
                __(
                    'Cannot delete satisfaction with id %1',
                    $satisfaction->getId()
                ),
                $e
            );
        }

        unset($this->instances[$id]);

        return true;
    }

    /**
     * @param int $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function deleteById($id)
    {
        /** @var \Mirasvit\Helpdesk\Model\Satisfaction $satisfaction */
        $satisfaction = $this->get($id);

        return $this->delete($satisfaction);
    }

    /**
     * @param Message $message
     * @return Satisfaction|null
     */
    public function getByMessage($message)
    {
        $satisfactions = $this->satisfactionCollectionFactory->create()
            ->addFieldToFilter('message_id', $message->getId());

        if ($satisfactions->count()) {
            return $satisfactions->getFirstItem();
        } else {
            return null;
        }
    }
}
