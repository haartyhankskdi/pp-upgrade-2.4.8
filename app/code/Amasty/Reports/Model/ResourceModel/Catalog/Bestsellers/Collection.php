<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Catalog\Bestsellers;

use Amasty\Reports\Model\ResourceModel\Filters\AddFromFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddStoreFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddToFilter;
use Amasty\Reports\Model\Utilities\AddReportRules;
use Amasty\Reports\Model\Utilities\CreateUniqueHash;
use Amasty\Reports\Traits\ImageTrait;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Helper;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Collection
{
    use ImageTrait;

    /**
     * @var Attribute
     */
    private $eavAttribute;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

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
     * @var CreateUniqueHash
     */
    private $createUniqueHash;

    /**
     * @var AddReportRules
     */
    private $addReportRules;

    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Snapshot $entitySnapshot,
        Helper $coreResourceHelper,
        Attribute $eavAttribute,
        AddFromFilter $addFromFilter,
        AddToFilter $addToFilter,
        AddStoreFilter $addStoreFilter,
        CreateUniqueHash $createUniqueHash,
        AddReportRules $addReportRules,
        MetadataPool $metadataPool,
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
        $this->eavAttribute = $eavAttribute;
        $this->metadataPool = $metadataPool;
        $this->addFromFilter = $addFromFilter;
        $this->addToFilter = $addToFilter;
        $this->addStoreFilter = $addStoreFilter;
        $this->createUniqueHash = $createUniqueHash;
        $this->addReportRules = $addReportRules;
    }

    /**
     * @param $collection
     * @return mixed
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
    public function joinCategoryTable($collection)
    {
        $collection->getSelect()
            ->join(
                ['sales_order_item' => $this->getTable('sales_order_item')],
                'sales_order_item.order_id = main_table.entity_id'
            )
            ->where('sales_order_item.parent_item_id IS NULL')
        ;
    }

    /**
     * @param $collection
     */
    public function applyBaseFilters($collection)
    {
        $this->joinCategoryTable($collection);
        $this->joinThumbnailAttribute($collection, 'sales_order_item');
        $collection->getSelect()
            ->reset(Select::COLUMNS);
        $collection->getSelect()
            ->columns([
                'total' => 'SUM(sales_order_item.base_row_total)',
                'qty' => 'FLOOR(SUM(sales_order_item.qty_ordered))',
                'sku' => 'sales_order_item.sku',
                'name' => 'sales_order_item.name',
                'order_id' => 'CONCAT(sales_order_item.order_id,\'' . $this->createUniqueHash->execute() . '\')',
                'product_id' => 'sales_order_item.product_id',
                'thumbnail' => 'attributes.value'
            ]);
    }

    /**
     * @param $collection
     */
    public function applyToolbarFilters($collection)
    {
        $this->addReportRules->execute($collection);
        $this->addFromFilter->execute($collection);
        $this->addToFilter->execute($collection);
        $this->addStoreFilter->execute($collection);
        $this->addGroupBy($collection);
    }

    /**
     * @param $collection
     */
    public function addGroupBy($collection)
    {
        $collection->getSelect()->group("sales_order_item.sku");
        $collection->getSelect()->order('FLOOR(SUM(sales_order_item.qty_ordered)) DESC');
    }
}
