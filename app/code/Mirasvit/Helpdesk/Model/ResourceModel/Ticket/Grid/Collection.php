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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Exception\LocalizedException;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Collection as TicketCollection;
use Mirasvit\Helpdesk\Model\SearchFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory;
use Mirasvit\Helpdesk\Helper\Field;
use Mirasvit\Helpdesk\Helper\Permission;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Collection for displaying grid of cms blocks.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends TicketCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $aggregations;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Permission
     */
    private $helpdeskPermission;

    /**
     * @param string                                                          $mainTable
     * @param string                                                          $eventPrefix
     * @param string                                                          $eventObject
     * @param string                                                          $resourceModel
     * @param string                                                          $model
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SearchFactory $searchFactory,
        CollectionFactory $ticketCollectionFactory,
        Field $helpdeskField,
        Permission $helpdeskPermission,
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = 'Magento\Framework\View\Element\UiComponent\DataProvider\Document',
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->helpdeskPermission = $helpdeskPermission;
        parent::__construct(
            $searchFactory,
            $ticketCollectionFactory,
            $helpdeskField,
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $storeManager,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    /**
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     *
     * @return void
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * Get search criteria.
     * @return \Magento\Framework\Api\SearchCriteriaInterface|bool
     */
    public function getSearchCriteria()
    {
        return false;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count.
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[]|array $items
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * @param int $ticketId
     *
     * @return int|bool
     */
    public function getNextTicket($ticketId)
    {
        $collection = $this->applyDefaultOrder()->setPageSize(100);

        $pages       = $collection->getLastPageNumber();
        $currentPage = 1;

        do {
            $collection->setCurPage($currentPage)->load();

            $next = false;
            $hit  = 0;

            foreach ($collection as $ticket) {
                if ($hit == 1) {
                    $next = $ticket->getId();
                    break 2;
                }
                if ($ticketId == $ticket->getId()) {
                    $hit = 1;
                }
            }
            $currentPage++;

            $collection->clear();
        } while ($currentPage <= $pages);

        return $next;
    }

    /**
     * @param int $ticketId
     *
     * @return int|bool
     */
    public function getPrevTicket($ticketId)
    {
        $collection = $this->applyDefaultOrder()->setPageSize(100);

        $pages       = $collection->getLastPageNumber();
        $currentPage = 1;

        do {
            $collection->setCurPage($currentPage)->load();

            $prev = false;

            foreach ($collection as $ticket) {
                if ($ticketId == $ticket->getId()) {
                    break (2);
                }
                $prev = $ticket->getId();
            }

            $currentPage--;

            $collection->clear();
        } while ($currentPage >= $pages);

        return $prev;
    }

    /**
     * {@inheritdoc}
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        if ($permission = $this->helpdeskPermission->getPermission()) {
            $departmentIds = $permission->getDepartmentIds();
            if (empty($permission->getDepartmentIds())) {
                throw new LocalizedException(
                    __('You don\'t have permissions to read this ticket. Please, contact your administrator.'));
            }
            if (!in_array(0, $departmentIds)) {
                $select = $this->getSelect();
                $select->where('main_table.department_id in (' . implode(',', $departmentIds) . ')');
            }
        }
    }
}
