<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\AbandonedCart\Model\ResourceModel\MailsLog\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Search\AggregationInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Webkul\AbandonedCart\Model\ResourceModel\MailsLog\Collection as MailsLogCollection;

class Collection extends MailsLogCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $_aggregations;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entity
     * @param \Psr\Log\LoggerInterface $logger
     * @param  FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $event
     * @param \Magento\Store\Model\StoreManagerInterface $store
     * @param mixed|null $mainTable
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $eventPrefix
     * @param mixed $eventObject
     * @param mixed $resourceModel
     * @param string $model
     * @param null $connection
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $resource
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct(
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
        return $this->_aggregations;
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @param AggregationInterface $aggregations
     *
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->_aggregations = $aggregations;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * Retrieve all ids for collection
     * Backward compatibility with EAV collection.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        $ids = $this->_getAllIdsSelect($limit, $offset);
        return $this->getConnection()->fetchCol($ids, $this->_bindParams);
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $search
     *
     * @return $this
     */
    public function setSearchCriteria(SearchCriteriaInterface $search = null)
    {
        return $this;
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     *
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Create all ids retrieving select with limitation
     * Backward compatibility with EAV collection
     *
     * @param int $limit
     * @param int $offset
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    protected function _getAllIdsSelect($limit = null, $offset = null)
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $idsSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $idsSelect->columns($this->getResource()->getIdFieldName(), 'main_table');
        $idsSelect->limit($limit, $offset);
        return $idsSelect;
    }

    /**
     * Apply filters before rendering the collection
     */
    protected function _renderFiltersBefore()
    {
        $quoteTable = $this->getTable('quote');
        $logsTable = $this->getTable('wk_abandoned_cart_mail_logs');
        $joinTable = $this->getTable('customer_grid_flat');

        $this->getSelect()
            ->join(
                $quoteTable." as quote",
                'main_table.quote_id = quote.entity_id'
            );
        $this->getSelect()->join(
            $joinTable.' as cgf',
            'quote.customer_id = cgf.entity_id',
            [
                'name'=>'name'
            ]
        );
        $this->getSelect()->reset("columns")
                            ->columns('main_table.entity_id')
                            ->columns('main_table.sent_by')
                            ->columns('main_table.sent_on')
                            ->columns('main_table.mode')
                            ->columns('cgf.name')
                            ->columns('quote.entity_id as quote_id');
        parent::_renderFiltersBefore();
    }
}
