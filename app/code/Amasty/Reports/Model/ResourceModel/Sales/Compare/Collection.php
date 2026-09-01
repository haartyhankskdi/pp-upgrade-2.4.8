<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Sales\Compare;

use Amasty\Reports\Model\ResourceModel\Filters\AddFromFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddInterval;
use Amasty\Reports\Model\ResourceModel\Filters\AddOrderStatusFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddStoreFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddToFilter;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Helper as DbHelper;
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
        AddInterval $addInterval,
        AddFromFilter $addFromFilter,
        AddToFilter $addToFilter,
        AddStoreFilter $addStoreFilter,
        AddOrderStatusFilter $addStatusFilter,
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

    public function prepareCollection($from = null, $to = null)
    {
        $this->applyBaseFilters();
        $this->addFromFilter->execute($this, 'created_at', 'main_table', $from);
        $this->addToFilter->execute($this, 'created_at', 'main_table', $to);
        $this->addStoreFilter->execute($this);
        $this->addInterval->execute($this);
        $this->addStatusFilter->execute($this);

        return $this;
    }

    /**
     * @return void
     */
    public function applyBaseFilters()
    {
        $this->getSelect()
            ->columns([
                'total_orders' => 'COUNT(entity_id)',
                'total_items' => 'SUM(total_item_count)',
                'subtotal' => 'SUM(base_subtotal)',
                'tax' => 'SUM(base_tax_amount)',
                'shipping' => 'SUM(base_shipping_amount)',
                'discounts' => 'SUM(base_discount_amount)',
                'total' => 'SUM(base_grand_total)',
                'invoiced' => 'SUM(base_total_invoiced)',
                'refunded' => 'SUM(base_total_refunded)',
            ])
            ->order('main_table.created_at ASC');
    }
}
