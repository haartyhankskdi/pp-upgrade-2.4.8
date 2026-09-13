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
use Mirasvit\Helpdesk\Api\Data\FieldInterface;
use Mirasvit\Helpdesk\Api\Data\FieldSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\FieldSearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Repository\FieldRepositoryInterface;
use Mirasvit\Helpdesk\Model\FieldFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Field as FieldResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;

class FieldRepository implements FieldRepositoryInterface
{
    private $fieldFactory;

    private $fieldResource;

    private $collectionFactory;

    private $searchResultsFactory;

    public function __construct(
        FieldFactory $fieldFactory,
        FieldResource $fieldResource,
        FieldCollectionFactory $collectionFactory,
        FieldSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->fieldFactory         = $fieldFactory;
        $this->fieldResource        = $fieldResource;
        $this->collectionFactory    = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function get(int $fieldId): FieldInterface
    {
        $field = $this->fieldFactory->create();
        $this->fieldResource->load($field, $fieldId);

        if (!$field->getId()) {
            throw new NoSuchEntityException(__('Field with id "%1" does not exist.', $fieldId));
        }

        return $field;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): FieldSearchResultsInterface
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
