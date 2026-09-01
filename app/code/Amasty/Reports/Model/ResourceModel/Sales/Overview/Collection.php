<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Sales\Overview;

use Amasty\Reports\Model\ResourceModel\Filters\AddFromFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddInterval;
use Amasty\Reports\Model\ResourceModel\Filters\AddOrderStatusFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddStoreFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddToFilter;
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
     * @var AddInterval
     */
    private $addInterval;

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
        AddInterval $addInterval,
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

        $this->addInterval = $addInterval;
        $this->addFromFilter = $addFromFilter;
        $this->addToFilter = $addToFilter;
        $this->addStoreFilter = $addStoreFilter;
        $this->addStatusFilter = $addStatusFilter;
    }

    /**
     * @param \Amasty\Reports\Model\ResourceModel\Sales\Overview\Grid\Collection $collection
     * @return \Amasty\Reports\Model\ResourceModel\Sales\Overview\Grid\Collection $collection
     */
    public function prepareCollection($collection)
    {
        $this->applyBaseFilters($collection);
        $this->applyToolbarFilters($collection);
        return $collection;
    }

    /**
     * @param $collection
     */
    public function applyBaseFilters($collection)
    {
        $collection->getSelect()->reset(Select::COLUMNS);
        $collection->getSelect()
            ->columns([
                    'total_orders' => 'COUNT(entity_id)',
                    'total_items'  => 'SUM(total_item_count)',
                    'subtotal'     => 'SUM(base_subtotal)',
                    'tax'          => 'SUM(base_tax_amount)',
                    'shipping'     => 'SUM(base_shipping_amount)',
                    'discounts'    => 'SUM(base_discount_amount)',
                    'total'        => 'SUM(base_grand_total)',
                    'invoiced'     => 'IFNULL(SUM(base_total_invoiced), 0)',
                    'refunded'     => 'IFNULL(SUM(base_total_refunded), 0)'
            ]);
        if ($collection->getFlag('force_sorting')) {
            $collection->getSelect()->order('period ' . \Magento\Framework\DB\Select::SQL_ASC);
        }
    }

    /**
     * @param $collection
     */
    public function applyToolbarFilters($collection)
    {
        $this->addFromFilter->execute($collection);
        $this->addToFilter->execute($collection);
        $this->addStoreFilter->execute($collection);
        $this->addInterval->execute($collection);
        $this->addStatusFilter->execute($collection);
    }

    /**
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult $collection
     *
     * @return array
     */
    public function getTotals($collection)
    {
        $collection->_renderFilters()->_renderOrders()->_renderLimit();
        $rowsSelect = clone $collection->getSelect();
        $totalsSelect = $this->getConnection()->select()->from(['rows' => $rowsSelect], [
            'total_orders' => 'SUM(total_orders)',
            'total_items' => 'SUM(total_items)',
            'subtotal' => 'SUM(subtotal)',
            'tax' => 'SUM(tax)',
            'shipping' => 'SUM(shipping)',
            'discounts' => 'SUM(discounts)',
            'total' => 'SUM(total)',
            'invoiced' => 'SUM(invoiced)',
            'refunded' => 'SUM(refunded)'
        ]);

        return $this->getConnection()->fetchRow($totalsSelect);
    }
}
