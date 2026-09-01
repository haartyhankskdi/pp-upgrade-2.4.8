<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Catalog\ByBrands;

use Amasty\Reports\Model\ConfigProvider;
use Amasty\Reports\Model\ResourceModel\Filters\AddFromFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddStoreFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddToFilter;
use Amasty\Reports\Model\Utilities\CreateUniqueHash;
use Amasty\Reports\Model\Utilities\JoinCustomAttribute;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Item\Collection
{
    /**
     * @var string
     */
    protected $_idFieldName = '';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

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
     * @var JoinCustomAttribute
     */
    private $joinCustomAttribute;

    /**
     * @var CreateUniqueHash
     */
    private $createUniqueHash;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Snapshot $entitySnapshot,
        AddFromFilter $addFromFilter,
        AddToFilter $addToFilter,
        AddStoreFilter $addStoreFilter,
        JoinCustomAttribute $joinCustomAttribute,
        CreateUniqueHash $createUniqueHash,
        ConfigProvider $configProvider,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $entitySnapshot,
            $connection,
            $resource
        );

        $this->configProvider = $configProvider;
        $this->addFromFilter = $addFromFilter;
        $this->addToFilter = $addToFilter;
        $this->addStoreFilter = $addStoreFilter;
        $this->joinCustomAttribute = $joinCustomAttribute;
        $this->createUniqueHash = $createUniqueHash;
    }

    /**
     * @param $collection
     */
    public function prepareCollection($collection)
    {
        $this->joinOrderTable($collection);
        $this->joinEavAttribute($collection);
        $this->joinChilds($collection);
        $this->applyToolbarFilters($collection);
    }

    /**
     * @param $collection
     */
    public function joinOrderTable($collection)
    {
        $collection->getSelect()->join(
            ['sales_order' => $this->getTable('sales_order')],
            'main_table.order_id = sales_order.entity_id'
        );
    }

    /**
     * @param $collection
     */
    public function joinEavAttribute($collection)
    {
        $eav = $this->configProvider->getReportBrand();

        $entityId = sprintf(
            'CONCAT(eaov1_%1$s.value,\'' . $this->createUniqueHash->execute() . '\')',
            $eav
        );
        $this->joinCustomAttribute->execute($collection, $eav);

        $collection->getSelect()
            ->reset(Select::COLUMNS)
            ->columns([
                'name' => sprintf('eaov1_%1$s.value', $eav),
                'total_orders' => 'COUNT(DISTINCT main_table.order_id)',
                'qty' => 'FLOOR(SUM(main_table.qty_ordered))',
                'items_ordered' => 'COUNT(main_table.qty_ordered)',
                'total' => 'SUM(IF(soi.total IS NOT NULL AND soi.total != 0, soi.total, main_table.base_row_total))',
                'tax' => 'SUM(main_table.base_tax_amount)',
                'discounts' => 'SUM(IF(soi.base_discount_amount IS NOT NULL AND soi.base_discount_amount != 0, '
                    . 'soi.base_discount_amount, main_table.base_discount_amount))',
                'invoiced' => 'SUM(IF(soi.invoiced IS NOT NULL AND soi.invoiced != 0, '
                    . 'soi.invoiced, main_table.base_row_invoiced))',
                'refunded' => 'SUM(IF(soi.refunded IS NOT NULL AND soi.refunded != 0, '
                    . 'soi.refunded, main_table.base_amount_refunded))',
                'entity_id' => $entityId
            ])->where(
                'main_table.parent_item_id IS NULL AND ' .
                sprintf('(eaov1_%1$s.value IS NOT NULL)', $eav)
            );
    }

    /**
     * @param $collection
     */
    public function applyToolbarFilters($collection)
    {
        $this->addFromFilter->execute($collection, 'created_at', 'sales_order');
        $this->addToFilter->execute($collection, 'created_at', 'sales_order');
        $this->addStoreFilter->execute($collection, 'sales_order');
    }

    /**
     * @param AbstractCollection $collection
     */
    private function joinChilds($collection)
    {
        $childsSelect = $this->getConnection()->select()->from(
            $this->getTable('sales_order_item'),
            [
                'total' => 'SUM(base_row_total)',
                'invoiced' => 'SUM(base_row_invoiced)',
                'refunded' => 'SUM(base_amount_refunded)',
                'base_discount_amount' => 'SUM(base_discount_amount)',
                'parent_item_id'
            ]
        )->group(
            'parent_item_id'
        );

        $collection->getSelect()->joinLeft(
            ['soi' => $childsSelect],
            'soi.parent_item_id = main_table.item_id',
            ''
        );
    }
}
