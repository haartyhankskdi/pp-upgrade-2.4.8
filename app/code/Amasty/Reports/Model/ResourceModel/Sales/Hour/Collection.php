<?php

namespace Amasty\Reports\Model\ResourceModel\Sales\Hour;

use Amasty\Reports\Model\ResourceModel\Filters\AddFromFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddOrderStatusFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddStoreFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddToFilter;
use Amasty\Reports\Model\Utilities\CreateUniqueHash;
use Amasty\Reports\Model\Utilities\TimeZoneExpressionModifier;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Collection
{
    /**
     * @var AddFromFilter
     */
    private $addFromFilter;

    /**
     * @var AddToFilter
     */
    private $addToFilter;

    /**
     * @var AddStoreFilter
     */
    private $addStoreFilter;

    /**
     * @var AddOrderStatusFilter
     */
    private $addStatusFilter;

    /**
     * @var TimeZoneExpressionModifier
     */
    private $expressionModifier;

    /**
     * @var CreateUniqueHash
     */
    private $createUniqueHash;

    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Snapshot $entitySnapshot,
        DbHelper $coreResourceHelper,
        AddFromFilter $addFromFilter,
        AddToFilter $addToFilter,
        AddStoreFilter $addStoreFilter,
        AddOrderStatusFilter $addStatusFilter,
        TimeZoneExpressionModifier $expressionModifier,
        CreateUniqueHash $createUniqueHash,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $entitySnapshot,
            $coreResourceHelper,
            $connection,
            $resource
        );

        $this->addFromFilter = $addFromFilter;
        $this->addToFilter = $addToFilter;
        $this->addStoreFilter = $addStoreFilter;
        $this->addStatusFilter = $addStatusFilter;
        $this->expressionModifier = $expressionModifier;
        $this->createUniqueHash = $createUniqueHash;
    }

    /**
     * @param \Amasty\Reports\Model\ResourceModel\Sales\Hour\Grid\Collection $collection
     */
    public function prepareCollection($collection)
    {
        $this->applyBaseFilters($collection);
        $this->applyToolbarFilters($collection);
    }

    /**
     * @param $collection
     */
    public function applyBaseFilters($collection)
    {
        $fieldExpression = $this->expressionModifier->execute('main_table.created_at');
        $collection->getSelect()
            ->reset(Select::COLUMNS)
            ->columns([
                'period' => sprintf('HOUR(%s)', $fieldExpression),
                'total_orders' => 'COUNT(entity_id)',
                'total_items' => 'SUM(total_item_count)',
                'subtotal' => 'SUM(base_subtotal)',
                'tax' => 'SUM(base_tax_amount)',
                'status' => 'status',
                'shipping' => 'SUM(base_shipping_amount)',
                'discounts' => 'SUM(base_discount_amount)',
                'total' => 'SUM(base_grand_total)',
                'invoiced' => 'IFNULL(SUM(base_total_invoiced), 0)',
                'refunded' => 'IFNULL(SUM(base_total_refunded), 0)',
                'entity_id' => 'CONCAT(entity_id,\'' . $this->createUniqueHash->execute() . '\')'
            ])
        ;
    }

    /**
     * @param $collection
     */
    public function applyToolbarFilters($collection)
    {
        $this->addFromFilter->execute($collection);
        $this->addToFilter->execute($collection);
        $this->addStoreFilter->execute($collection);
        $this->addGroupBy($collection);
        $this->addStatusFilter->execute($collection);
    }

    /**
     * @param $collection
     */
    public function addGroupBy($collection)
    {
        $collection->getSelect()
            ->group("HOUR(created_at)")
        ;
    }
}
