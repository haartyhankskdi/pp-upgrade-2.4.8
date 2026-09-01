<?php

namespace Amasty\Reports\Model\ResourceModel\Customers\Conversion;

use Amasty\Reports\Model\ResourceModel\Filters\AddFromFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddToFilter;
use Amasty\Reports\Model\ResourceModel\Filters\RequestFiltersProvider;
use Amasty\Reports\Model\Utilities\TimeZoneExpressionModifier;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Customer\Model\ResourceModel\Visitor\Collection
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
     * @var RequestFiltersProvider
     */
    private $filtersProvider;

    /**
     * @var TimeZoneExpressionModifier
     */
    private $timeZoneExpressionModifier;

    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AddFromFilter $addFromFilter,
        AddToFilter $addToFilter,
        RequestFiltersProvider $filtersProvider,
        TimeZoneExpressionModifier $timeZoneExpressionModifier,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );

        $this->addFromFilter = $addFromFilter;
        $this->addToFilter = $addToFilter;
        $this->filtersProvider = $filtersProvider;
        $this->timeZoneExpressionModifier = $timeZoneExpressionModifier;
    }

    public function prepareCollection(AbstractCollection $collection): void
    {
        $this->applyBaseFilters($collection);
    }

    private function applyBaseFilters(AbstractCollection $collection): void
    {
        $collection->getSelect()->reset(Select::COLUMNS);

        [$periodSelect, $group] = $this->getIntervalSelectAndGroupBy($collection, 'main_table.last_visit_at');

        [$orderPeriodSelect, $orderGroup] = $this->getIntervalSelectAndGroupBy(
            $collection,
            'orderTable.created_at'
        );

        $convertionExpr = "ROUND(COUNT(DISTINCT orderTable.entity_id) / COUNT(DISTINCT main_table.session_id) * 100)";
        $exculdedStates = [Order::STATE_CANCELED, Order::STATE_CLOSED];
        $collection->getSelect()
            ->columns([
                'period' => $periodSelect,
                'orders' => 'COUNT(DISTINCT orderTable.entity_id)',
                'visitors' => 'COUNT(DISTINCT main_table.session_id)',
                'conversion' => $convertionExpr,
            ])
            ->joinLeft(
                ['orderTable' => $this->getTable('sales_order')],
                $periodSelect . ' = ' . $orderPeriodSelect
                . " AND (orderTable.state NOT IN('" . implode("','", $exculdedStates) . "'))"
                . " AND orderTable.remote_ip IS NOT NULL",
                []
            )

            ->order($periodSelect . ' DESC')
            ->group($group);

        $this->addFromFilter->execute($collection, 'last_visit_at', 'main_table');
        $this->addToFilter->execute($collection, 'last_visit_at', 'main_table');
    }

    private function getIntervalSelectAndGroupBy(AbstractCollection $collection, string $field): array
    {
        $field = $this->timeZoneExpressionModifier->execute($field);
        $filters = $this->filtersProvider->execute();
        $interval = $filters['interval'] ?? 'day';
        $collection->getSelect()->reset(Select::GROUP);

        switch ($interval) {
            case 'year':
                $select = $group = sprintf('YEAR(DATE(%s))', $field);
                break;
            case 'month':
                $select = 'CONCAT(YEAR(DATE(%1$s)), \'-\', MONTH(DATE(%1$s)))';
                $select = sprintf($select, $field);
                $group = sprintf('MONTH(DATE(%s))', $field);
                break;
            case 'week':
                $select = 'CONCAT(ADDDATE(DATE(DATE(%1$s)), INTERVAL 1-DAYOFWEEK(DATE(%1$s)) DAY), '
                    . '" - ", ADDDATE(DATE(DATE(%1$s)), INTERVAL 7-DAYOFWEEK(DATE(%1$s)) DAY))';
                $select = sprintf($select, $field);
                $group = sprintf('WEEK(DATE(%s))', $field);
                break;
            case 'day':
            default:
                $select = $group = sprintf('DATE(DATE(%s))', $field);
                break;
        }

        return [$select, $group];
    }
}
