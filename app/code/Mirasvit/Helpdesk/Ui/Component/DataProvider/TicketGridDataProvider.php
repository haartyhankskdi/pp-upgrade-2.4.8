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



namespace Mirasvit\Helpdesk\Ui\Component\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Mirasvit\Helpdesk\Logger\FatalLogger;
use Mirasvit\Core\Service\SerializeService as Serializer;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TicketGridDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var FatalLogger
     */
    private $logger;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var \Magento\Framework\View\Element\UiComponent\DataProvider\RegularFilter
     */
    private $regularFilter;
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @param FatalLogger $logger
     * @param ManagerInterface $messageManager
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\RegularFilter $regularFilter
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        FatalLogger $logger,
        ManagerInterface $messageManager,
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory,
        \Magento\Framework\View\Element\UiComponent\DataProvider\Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        \Magento\Framework\View\Element\UiComponent\DataProvider\RegularFilter $regularFilter,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->collectionFactory = $collectionFactory;
        $this->regularFilter = $regularFilter;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'status_id') {
            $filter->setField('main_table.status_id');
        }
        if ($filter->getField() == 'priority_id') {
            $filter->setField('main_table.priority_id');
        }
        if ($filter->getField() == 'department_id') {
            $filter->setField('main_table.department_id');
        }
        if ($filter->getField() == 'code') {
            $filter->setField('main_table.code');
        }
        if ($filter->getField() == 'subject') {
            $filter->setField('main_table.subject');
        }
        if ($filter->getField() == 'created_at') {
            $filter->setField('main_table.created_at');
        }
        if ($filter->getField() == 'user_id') {
            $filter->setField('main_table.user_id');
        }

        parent::addFilter($filter);
    }

    /**
     * Returns Search result
     *
     * @return \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Collection
     */
    public function getSearchResult()
    {
        $searchCriteria = $this->getSearchCriteria();
        if (!$this->isSearchFulltext($searchCriteria)) {
            /** @var \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Grid\Collection $searchResult */
            $searchResult = $this->reporting->search($searchCriteria);

            return $searchResult->joinStatuses();
        }

        /** @var \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Grid\Collection $ticketCollection */
        $ticketCollection = $this->collectionFactory->getReport($this->getSearchCriteria()->getRequestName());

        $this->search($ticketCollection, $this->getSearchCriteria());
        $select = clone $ticketCollection->getSelect();

        $ticketCollection->getSelect()->reset();
        $ticketCollection->getSelect()->setPart(\Zend_Db_Select::COLUMNS, [
            ['main_table', '*', null],
        ]);

        $ticketCollection->getSelect()->setPart(\Zend_Db_Select::FROM, [
            'main_table' => [
                'joinType'      => 'from',
                'schema'        => null,
                'tableName'     => new \Zend_Db_Expr('(' . $select->__toString() . ')'),
                'joinCondition' => null,
            ],
        ]);

        $ticketCollection->getSelect()->setPart(\Zend_Db_Select::ORDER, [
            ['search_prior', 'DESC'],
        ]);
        $select = $ticketCollection->getSelect();
        $select->limit($this->getSearchCriteria()->getCurrentPage(), $this->getSearchCriteria()->getPageSize());

        return $ticketCollection;
    }

    /**
     * This method logs ticket with unsupported content
     * {@inheritdoc}
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $result = parent::searchResultToOutput($searchResult);

        if (!isset($result['items'])) {
            return $result;
        }

        $items = [];

        foreach ($result['items'] as $item) {

            $cleanItem = $this->sanitizeItem($item);

            if (Serializer::encode($cleanItem) === null) {

                $this->logger->critical('JSON_ERROR', [
                    'item' => base64_encode(serialize($item)),
                    'error' => json_last_error_msg()
                ]);

                $this->messageManager->addErrorMessage(
                    __('Some tickets could not be parsed. Please check logs.')
                );
            } else {
                $items[] = $cleanItem;
            }
        }

        $result['items'] = $items;
        return $result;
    }

    /**
     * sanitize item for JSON serialization
     */
    protected function sanitizeItem(array $item): array
    {
        foreach ($item as $key => $value) {

            if (is_resource($value)) {
                unset($item[$key]);
            }

            elseif (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $item[$key] = (string)$value;
                } else {
                    unset($item[$key]);
                }
            }

            elseif (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $item[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }

            elseif (is_array($value)) {
                $item[$key] = $this->sanitizeItem($value);
            }
        }

        return $item;
    }


    /**
     * @param \Magento\Framework\Api\Search\SearchCriteria $searchCriteria
     * @return bool
     */
    private function isSearchFulltext($searchCriteria)
    {
        $result = false;
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            /** @var \Magento\Framework\Api\Filter $filter */
            foreach ($filterGroup->getFilters() ?? [] as $filter) {
                if ($filter->getConditionType() == 'fulltext') {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Collection $collection
     * @param \Magento\Framework\Api\Search\SearchCriteria $searchCriteria
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function search($collection, $searchCriteria)
    {
        $collection->setPageSize((int) $searchCriteria->getPageSize());
        $collection->setCurPage((int) $searchCriteria->getCurrentPage());

        foreach ($searchCriteria->getSortOrders() ?? [] as $sortOrder) {
            if ($sortOrder->getField()) {
                $collection->setOrder($sortOrder->getField(), $sortOrder->getDirection());
            }
        }

        /** @var \Magento\Framework\Api\Search\FilterGroup $filterGroup */
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            /** @var \Magento\Framework\Api\Filter $filter */
            foreach ($filterGroup->getFilters() ?? [] as $filter) {
                if ($filter->getConditionType() == 'fulltext') {
                    $collection->addSearchAttributes($this->getSearchAttributes(), $filter->getValue());
                } else {
                    $this->regularFilter->apply($collection, $filter);
                }
            }
        }

        return $collection;
    }

    /**
     * @return array
     */
    public function getSearchAttributes()
    {
        $attributes = [
            'main_table.subject'         => [
                'priority'        => 100,
                'selectStatement' => 'main_table.subject',
            ],
            'main_table.ticket_id'       => [
                'priority'        => 0,
                'selectStatement' => 'main_table.ticket_id',
            ],
            'main_table.description'     => [
                'priority'        => 0,
                'selectStatement' => 'main_table.description',
            ],
            'main_table.code'            => [
                'priority'        => 10,
                'selectStatement' => 'main_table.code',
            ],
            'main_table.order_id'        => [
                'priority'        => 0,
                'selectStatement' => 'main_table.order_id',
            ],
            'main_table.search_index'    => [
                'priority'        => 0,
                'selectStatement' => 'main_table.search_index',
            ],
            'customer_email'             => [
                'priority'        => 0,
                'selectStatement' => 'customer_email',
            ]
        ];
        uasort($attributes, function ($elFirst, $elSecond) {
            if ($elFirst['priority'] == $elSecond['priority']) {
                return 0;
            }

            return $elFirst['priority'] < $elSecond['priority'] ? 1 : -1;
        });

        return $attributes;
    }
}
