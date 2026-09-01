<?php

namespace Amasty\Reports\Model\ResourceModel\Customers\Customers;

use Amasty\Reports\Traits\Filters;
use Magento\Framework\DB\Select;

class Collection extends \Magento\Customer\Model\ResourceModel\Customer\Collection
{
    use Filters;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Amasty\Reports\Helper\Data
     */
    protected $helper;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\DataObject\Copy\Config $fieldsetConfig,
        \Magento\Framework\App\RequestInterface $request, // TODO move it out of here
        \Amasty\Reports\Helper\Data $helper,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $modelName = self::CUSTOMER_MODEL_NAME
    ) {
        $this->_fieldsetConfig = $fieldsetConfig;
        $this->_modelName = $modelName;
        $this->request = $request;
        $this->helper = $helper;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $entitySnapshot,
            $fieldsetConfig,
            $connection,
            $modelName
        );
    }

    /**
     * @return string
     */
    public function getMainTable()
    {
        switch ($this->getInterval()) {
            case 'day':
                $table = 'amasty_reports_customers_customers_daily';
                break;
            case 'week':
                $table = 'amasty_reports_customers_customers_weekly';
                break;
            case 'month':
                $table = 'amasty_reports_customers_customers_monthly';
                break;
            case 'year':
                $table = 'amasty_reports_customers_customers_yearly';
                break;
        }

        return $this->getTable($table);
    }

    /**
     * @param $collection
     * @param string $tablePrefix
     */
    public function prepareCollection($collection, $tablePrefix = 'main_table')
    {
        $this->applyBaseFilters($collection);
        $this->applyToolbarFilters($collection, $tablePrefix);
    }

    /**
     * @param $collection
     */
    public function applyBaseFilters($collection)
    {
        $collection->getSelect()->reset(Select::FROM);
        $this->addTableFilter($collection);
        $collection->getSelect()->reset(Select::COLUMNS);
        $collection->getSelect()
            ->columns([
                'period' => 'period',
                'new_accounts' => 'new_accounts',
                'orders' => 'orders',
                'reviews' => 'reviews',
                'entity_id' => 'CONCAT(period,store_id,\''.$this->createUniqueEntity().'\')'
            ]);
    }

    /**
     * @param $collection
     * @param string $tablePrefix
     */
    public function applyToolbarFilters($collection, $tablePrefix = 'main_table')
    {
        $this->addFromFilter($collection, $tablePrefix);
        $this->addToFilter($collection, $tablePrefix);
        $this->addCurrentStoreFilter($collection, $tablePrefix);
        $this->addGroupBy($collection, $tablePrefix);
    }

    /**
     * @param $collection
     */
    public function addTableFilter($collection)
    {
        $collection->getSelect()->from(['main_table' => $this->getMainTable()]);
    }

    /**
     * @param $collection
     * @param string $tablePrefix
     */
    public function addFromFilter($collection, $tablePrefix = 'main_table')
    {
        $filters = $this->getRequestParams();
        $from = isset($filters['from']) ? $filters['from'] : date('Y-m-d', $this->helper->getDefaultFromDate());

        switch ($this->getInterval()) {
            case 'day':
                $expr = 'DATE('.$tablePrefix.'.period) >= ?';
                break;
            case 'week':
                $expr = "CONCAT(YEAR({$tablePrefix}.period), '-', "
                    . "WEEK({$tablePrefix}.period)) >= CONCAT(YEAR(?), '-', WEEK(?))";
                break;
            case 'month':
                $expr = "DATE(CONCAT(YEAR({$tablePrefix}.period), '-', MONTH({$tablePrefix}.period), '-1')) "
                    . ">= DATE(CONCAT(YEAR(?), '-', MONTH(?), '-1'))";
                break;
            case 'year':
                $expr = "YEAR({$tablePrefix}.period) >= YEAR(?)";
                break;
        }
        if ($from) {
            $collection->getSelect()->where($expr, $from);
        }
    }

    /**
     * @param $collection
     * @param string $tablePrefix
     */
    public function addToFilter($collection, $tablePrefix = 'main_table')
    {
        $filters = $this->getRequestParams();
        $to = isset($filters['to']) ? $filters['to'] : date('Y-m-d');

        switch ($this->getInterval()) {
            case 'day':
                $expr = 'DATE('.$tablePrefix.'.period) <= ?';
                break;
            case 'week':
                $expr = "CONCAT(YEAR({$tablePrefix}.period), '-', "
                    . "WEEK({$tablePrefix}.period)) <= CONCAT(YEAR(?), '-', WEEK(?))";
                break;
            case 'month':
                $expr = "DATE(CONCAT(YEAR({$tablePrefix}.period), '-', MONTH({$tablePrefix}.period), '-1')) "
                    . "<= DATE(CONCAT(YEAR(?), '-', MONTH(?), '-1'))";
                break;
            case 'year':
                $expr = "YEAR({$tablePrefix}.period) <= YEAR(?)";
                break;
        }
        if ($to) {
            $collection->getSelect()->where($expr, $to);
        }
    }

    /**
     * @param $collection
     * @param $tablePrefix
     */
    public function addGroupBy($collection, $tablePrefix)
    {
        $collection->getSelect()->group("DATE($tablePrefix.period)");
    }

    private function getInterval(): string
    {
        $filters = $this->getRequestParams();
        return $filters['interval'] ?? 'day';
    }
}
