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
 * @package   mirasvit/module-report-api
 * @version   1.0.95
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\ReportApi\Handler;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Mirasvit\ReportApi\Api\Config\AggregatorInterface;
use Mirasvit\ReportApi\Api\Config\CollectionInterface;
use Mirasvit\ReportApi\Api\Config\ColumnInterface;
use Mirasvit\ReportApi\Api\Config\RelationInterface;
use Mirasvit\ReportApi\Api\Config\TableInterface;
use Mirasvit\ReportApi\Api\Config\TypeInterface;
use Mirasvit\ReportApi\Api\RequestInterface;
use Mirasvit\ReportApi\Config\Entity\Column;
use Mirasvit\ReportApi\Config\Entity\Table;
use Mirasvit\ReportApi\Config\Schema;
use Mirasvit\ReportApi\Service\SelectService;

class Collection implements CollectionInterface
{
    protected $schema;

    /**
     * @var Select
     */
    private $select;

    private $selectService;

    private $resource;

    private $connection;

    /**
     * @var array
     */
    private $items = [];

    private $request;

    private $objectManager;

    private $selectFactory;

    private $ctes = [];

    private $scopeConfig;

    public function __construct(
        SelectFactory $selectFactory,
        SelectService $selectService,
        ResourceConnection $resource,
        ObjectManagerInterface $objectManager,
        Schema $schema,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource      = $resource;
        $this->selectService = $selectService;
        $this->selectFactory = $selectFactory;
        $this->select        = $selectFactory->create();
        $this->objectManager = $objectManager;
        $this->schema        = $schema;
        $this->scopeConfig   = $scopeConfig;
    }

    private function isUseCte()
    {
        return $this->scopeConfig->isSetFlag('mst_report/advanced/use_cte');
    }

    /**
     * @param RequestInterface $request
     *
     * @return $this|CollectionInterface
     * @throws \Zend_Db_Exception
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        $baseTable = $this->schema->getTable($request->getTable());

        $this->connection = $this->resource->getConnection($baseTable->getConnectionName());

        $this->select
            ->setBaseTable($baseTable)
            ->limitPage($request->getCurrentPage(), $request->getPageSize());

        foreach ($request->getColumns() as $identifier) {
            $column = $this->schema->getColumn($identifier);

            $this->selectService->applyPills($request, $column, $this->select);

            if (!$this->selectService->isAggregationRequired($baseTable, $column, $request)) {
                $this->select->addColumnToSelect($column);
            } else {
                /** @var Column $clone */
                /** @var Column $clone */
                $clone = clone $column;

                /** @var Table $table */
                $table = $this->isUseCte()
                    ? $this->ensureCte($column, $request, $baseTable)
                    : $this->selectService->createTemporaryTable($column, $request, $baseTable);

                $clone->setTable($table);

                if ($clone->getAggregator()->getType() == AggregatorInterface::TYPE_COUNT) {
                    $agg = $this->objectManager->create($this->schema->getAggregator(AggregatorInterface::TYPE_SUM));
                    $clone->setAggregator($agg);
                }

                if ($clone->getType()->getType() == TypeInterface::TYPE_PERCENT) {
                    $agg = $this->objectManager->create($this->schema->getAggregator(AggregatorInterface::TYPE_AVERAGE));
                    $clone->setAggregator($agg);
                }

                $clone->setExpression('%1');
                $clone->setFields([$clone->getName()]);

                $this->select->addColumnToSelect($clone, $column->getIdentifier());
                //                $select = $this->selectFactory->create();
                //                $select->from($clone->getTable()->getName(), [$clone->toDbExpr()]);
                //
                //                foreach ($this->schema->getRelations() as $r) {
                //                    if ($r->getLeftTable() === $clone->getTable() && $r->getRightTable() === $baseTable) {
                //                        $select->where($r->getCondition());
                //                    }
                //                }
                //
                //                $this->select->columns([
                //                    $column->getIdentifier() => new \Zend_Db_Expr('(' . $select . ')'),
                //                ]);
            }
        }

        $filterTables = [];
        foreach ($request->getFilters() as $filter) {
            $column = $this->schema->getColumn($filter->getColumn());

            if (!$this->selectService->isAggregationRequired($baseTable, $column, $request)
                || $this->select->isJoined($column->getTable())) {
                $this->select->addColumnToFilter($column, [
                    $filter->getConditionType() => $filter->getValue(),
                ]);
            } else {
                /** @var Table $table */
                if (isset($filterTables[$column->getIdentifier()])) {
                    $table = $filterTables[$column->getIdentifier()];
                } else {
                    $table = $this->isUseCte()
                        ? $this->ensureCte($column, $request, $baseTable)
                        : $this->selectService->createTemporaryTable($column, $request, $baseTable);

                    $filterTables[$column->getIdentifier()] = $table;
                }

                /** @var Column $clone */
                $clone = clone $column;
                $clone->setTable($table);

                // Only apply aggregation for filters when there are dimensions that would benefit from it
                // Without dimensions, filters should use raw CTE value (WHERE), not aggregated (HAVING)
                $hasDifferentDimensions = !empty($request->getDimensions());

                if ($hasDifferentDimensions) {
                    if ($clone->getAggregator()->getType() == AggregatorInterface::TYPE_COUNT) {
                        $agg = $this->objectManager->create($this->schema->getAggregator(AggregatorInterface::TYPE_SUM));
                        $clone->setAggregator($agg);
                    }

                    if ($clone->getType()->getType() == TypeInterface::TYPE_PERCENT) {
                        $agg = $this->objectManager->create($this->schema->getAggregator(AggregatorInterface::TYPE_AVERAGE));
                        $clone->setAggregator($agg);
                    }
                } else {
                    // No dimensions - use raw CTE value for per-row filtering
                    $agg = $this->objectManager->create($this->schema->getAggregator(AggregatorInterface::TYPE_NONE));
                    $clone->setAggregator($agg);
                }

                $clone->setExpression('%1');
                $clone->setFields([$clone->getName()]);

                $this->select->addColumnToFilter($clone, [
                    $filter->getConditionType() => $filter->getValue(),
                ]);
            }
        }

        foreach ($request->getSortOrders() as $sortOrder) {
            $column = $this->schema->getColumn($sortOrder->getColumn());

            if (!$this->selectService->isAggregationRequired($baseTable, $column, $request)) {
                $this->select->addColumnToOrder($column, $sortOrder->getDirection());
            } else {
                /** @var Table $table */
                $table = $this->isUseCte()
                    ? $this->ensureCte($column, $request, $baseTable)
                    : $this->selectService->createTemporaryTable($column, $request, $baseTable);

                /** @var Column $clone */
                $clone = clone $column;
                $clone->setTable($table);

                if ($clone->getAggregator()->getType() == AggregatorInterface::TYPE_COUNT) {
                    $agg = $this->objectManager->create($this->schema->getAggregator(AggregatorInterface::TYPE_SUM));
                    $clone->setAggregator($agg);
                }
                $clone->setExpression('%1');
                $clone->setFields([$clone->getName()]);

                $this->select->leftJoin(
                    [$table->getName() => $table->getName()],
                    $table->getPkField()->toDbExpr() . '=' . $baseTable->getPkField()->toDbExpr(),
                    []
                );
                $this->select->order($clone->toDbExpr() . ' ' . $sortOrder->getDirection());
            }
        }

        foreach ($request->getDimensions() as $dimension) {
            $column = $this->schema->getColumn($dimension);

            if (!$this->selectService->isAggregationRequired($baseTable, $column, $request)) {
                $this->select->addColumnToGroup($column);
            } else {
                /** @var Table $table */
                $table = $this->isUseCte()
                    ? $this->ensureCte($column, $request, $baseTable)
                    : $this->selectService->createTemporaryTable($column, $request, $baseTable);

                /** @var Column $clone */
                $clone = clone $column;
                $clone->setTable($table);
                $clone->setExpression('%1');
                $clone->setFields([$clone->getName()]);

                $this->select->addColumnToGroup($clone);
            }
        }

        return $this;
    }

    public function ensureCte(ColumnInterface $column, RequestInterface $request, TableInterface $baseTable)
    {
        $cteKey = $column->getIdentifier() . '_' . $baseTable->getName();

        if (!isset($this->ctes[$cteKey])) {
            $cte = $this->selectService->getCte($column, $request, $baseTable);
            $cteAlias = array_keys($cte)[0]; // Get the first (and only) key

            $this->ctes[$cteKey] = [
                'alias' => $cteAlias,
                'table' => $cte[$cteAlias]['table'],
                'select' => $cte[$cteAlias]['select']
            ];
        }

        return $this->ctes[$cteKey]['table'];
    }

    /**
     * @return int|void
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        $this->loadData();

        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function loadData()
    {
        $this->selectService->applyTimeZone($this->connection);
        $rows = $this->connection->fetchAll($this->buildQuery($this->select));
        $this->selectService->restoreTimeZone($this->connection);

        foreach ($rows as $row) {
            $this->items[] = $row;
        }

        return $this;
    }

    /**
     * @return \ArrayIterator|\Traversable
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $this->loadData();

        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->buildQuery($this->select);
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        $this->select = clone $this->select;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        $countSelect = clone $this->select;
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER)
            ->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
            ->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
            ->reset(\Magento\Framework\DB\Select::COLUMNS);

        $countSelect->columns();

        $tableName      = $this->resource->getTableName($this->request->getTable());

        try {
            $incrementField = $this->connection->getAutoIncrementField($tableName) ?: '*';
        } catch (\Exception $e) {
            // intentional no-op: no auto-increment field available, fall back to COUNT(*)
            $incrementField = '*';
        }

        $innerSelect = $countSelect->__toString();

        $pos = strpos($innerSelect, '*');

        $innerSelect = substr_replace($innerSelect, $incrementField, (int)$pos, 1);

        $select = $this->getCtesPrefix() . 'SELECT COUNT(*) FROM (' . $innerSelect . ') as cnt';

        $this->selectService->applyTimeZone($this->connection);
        $result = $this->connection->fetchOne($select);
        $this->selectService->restoreTimeZone($this->connection);

        return $result;
    }

    /**
     * {@inheritdoc}
     */

    public function getTotals()
    {
        $requestTableName = $this->request->getTable();

        try {
            $pk = $this->schema->getTable($requestTableName)->getPkField();
        } catch (\Exception $e) {
            // intentional no-op: no primary key to group by, use the un-grouped fallback totals
            return $this->getTotalsFallback();
        }

        $pkField = $pk->toDbExpr();

        // temporary fix for rma reports
        // detect for which cases old and new totals should be used
        if ($pkField == 'mst_rma_item.item_id') {
            return $this->getTotalsFallback();
        }

        $select = clone $this->select;
        $select->reset(\Magento\Framework\DB\Select::ORDER)
            ->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
            ->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

        $select->columns(['pk_count' => 'COUNT(DISTINCT ' . $pkField . ')']);

        $result = [];

        $this->selectService->applyTimeZone($this->connection);
        $rows = $this->connection->fetchAll($this->buildQuery($select));
        $this->selectService->restoreTimeZone($this->connection);

        if (empty($rows)) {
            foreach ($this->request->getColumns() as $columnId) {
                $column = $this->schema->getColumn($columnId);
                $result[$columnId] = $column->getType()->getValueType() === TypeInterface::VALUE_TYPE_NUMBER
                    ? 0
                    : null;
            }

            return $result;
        }

        $rowsCountForAvg = [];

        foreach ($rows as $row) {
            $pkCount = (int)$row['pk_count'];

            foreach ($row as $k => $v) {
                if ($k == 'pk_count') {
                    continue;
                }

                $column = $this->schema->getColumn($k);

                if (!isset($result[$k])) {
                    $result[$k] = null;
                }

                if ($column->getType()->getValueType() === TypeInterface::VALUE_TYPE_NUMBER) {
                    if (
                        $column->getAggregator()->getType() == AggregatorInterface::TYPE_AVERAGE
                        || $column->getType()->getType() == TypeInterface::TYPE_PERCENT
                    ) {
                        $result[$k] += (float)$v * $pkCount;

                        if (!isset($rowsCountForAvg[$k])) {
                            $rowsCountForAvg[$k] = 0;
                        }

                        $rowsCountForAvg[$k] += $pkCount;
                    } else {
                        $result[$k] += (float)$v;
                    }
                } else {
                    $result[$k] .= ',' . $v;
                }
            }
        }

        $columnNames = array_keys($result);
        foreach ($columnNames as $columnName) {
            if ($columnName == 'pk') {
                continue;
            }

            $column = $this->schema->getColumn($columnName);

            if ($this->selectService->getRelationType(
                    $column->getTable(),
                    $this->schema->getTable($this->request->getTable())
                ) == RelationInterface::TYPE_MANY) {
                $result[$columnName] = null;
                continue;
            }

            // This method sums the column across the grouped rows (the SELECT keeps its GROUP BY),
            // so the per-group aggregates add up to the correct grand total as long as no dimension
            // duplicates the base rows. A dimension whose table is on the "many" side of the base
            // table does duplicate them across groups (e.g. grouping a sales_order report by SKU or
            // Category), which over-counts the sum, so in that case the total cannot be trusted and
            // we suppress it instead of showing an inflated value (mirasvit/pm#380). This replaces
            // the obsolete blanket `count(dimensions) > 1` guard, which both suppressed valid totals
            // (row_total grouped by Customer + Status) and let inflated ones through (base-table
            // columns under a many-to-many dimension). The un-grouped getTotalsFallback() keeps its
            // own guard, because there fan-out would inflate the sum.
            if ($this->hasFanoutDimension()) {
                $result[$columnName] = null;
                continue;
            }

            if ($column->getType()->getValueType() === TypeInterface::VALUE_TYPE_STRING
                && $column->getAggregator()->getType() === AggregatorInterface::TYPE_CONCAT
            ) {
                $values = [];
                foreach (explode(',', $result[$columnName]) as $value) {
                    if ($value && !in_array($value, $values, true)) {
                        $values[] = $value;
                    }
                }

                $result[$columnName] = implode(', ', $values);
            } elseif (!in_array($column->getType()->getValueType(), [TypeInterface::VALUE_TYPE_NUMBER])) {
                $result[$columnName] = null;
            } elseif ($column->getAggregator()->getType() == AggregatorInterface::TYPE_AVERAGE) {
                $result[$columnName] /= (isset($rowsCountForAvg[$columnName]) && $rowsCountForAvg[$columnName] !==0 ? $rowsCountForAvg[$columnName] : count($rows));
            } elseif ($column->getType()->getType() == TypeInterface::TYPE_PERCENT) {
                $result[$columnName] /= (isset($rowsCountForAvg[$columnName]) && $rowsCountForAvg[$columnName] !==0 ? $rowsCountForAvg[$columnName] : count($rows));
            } elseif ($column->getType()->getType() == TypeInterface::TYPE_PK && $column->getAggregator()->getType() == AggregatorInterface::TYPE_NONE) {
                $result[$columnName] = null;
            }
        }

        return $result;
    }

    /**
     * Whether any requested dimension sits on a table that is on the "many" side of the base table.
     * Such a dimension duplicates the base rows across the GROUP BY groups, so the grouped totals
     * over-count and cannot be trusted. Same-table dimensions and dimensions reachable 1:1 are safe.
     *
     * @return bool
     */
    private function hasFanoutDimension()
    {
        try {
            $baseTable = $this->schema->getTable($this->request->getTable());
        } catch (\Exception $e) {
            // intentional no-op: base table unresolvable, assume no fanout dimension
            return false;
        }

        foreach ($this->request->getDimensions() as $dimension) {
            try {
                $dimensionTable = $this->schema->getColumn($dimension)->getTable();
            } catch (\Exception $e) {
                // intentional no-op: dimension column unresolvable, skip it when checking for fanout
                continue;
            }

            try {
                if ($this->selectService->getRelationType($dimensionTable, $baseTable) === RelationInterface::TYPE_MANY) {
                    return true;
                }
            } catch (\Exception $e) {
                // intentional no-op: dimension not relatable to base on a single path - cannot trust the total
                return true;
            }
        }

        return false;
    }

    public function getTotalsFallback()
    {
        $select = clone $this->select;
        $select->reset(\Magento\Framework\DB\Select::ORDER)
            ->reset(\Magento\Framework\DB\Select::LIMIT_COUNT)
            ->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET)
            ->reset(\Magento\Framework\DB\Select::GROUP);

        $result = [];

        $this->selectService->applyTimeZone($this->connection);
        $rows = $this->connection->fetchAll($this->buildQuery($select));
        $this->selectService->restoreTimeZone($this->connection);

        foreach ($rows as $row) {
            foreach ($row as $k => $v) {
                $column = $this->schema->getColumn($k);

                if (!isset($result[$k])) {
                    $result[$k] = null;
                }
                if ($column->getType()->getValueType() === TypeInterface::VALUE_TYPE_NUMBER) {
                    $result[$k] += (float)$v;
                } else {
                    $result[$k] .= ',' . $v;
                }
            }
        }

        $columnNames = array_keys($result);
        foreach ($columnNames as $columnName) {
            if ($columnName == 'pk') {
                continue;
            }

            $column = $this->schema->getColumn($columnName);

            if ($this->selectService->getRelationType(
                    $column->getTable(),
                    $this->schema->getTable($this->request->getTable())
                ) == RelationInterface::TYPE_MANY) {
                $result[$columnName] = null;
                continue;
            }

            if (count($this->request->getDimensions()) > 1 && $column->getTable()->getName() !== $this->request->getTable()) {
                $result[$columnName] = null;
                continue;
            }

            if ($column->getType()->getValueType() === TypeInterface::VALUE_TYPE_STRING
                && $column->getAggregator()->getType() === AggregatorInterface::TYPE_CONCAT
            ) {
                $values = [];
                foreach (explode(',', $result[$columnName]) as $value) {
                    if ($value && !in_array($value, $values, true)) {
                        $values[] = $value;
                    }
                }

                $result[$columnName] = implode(', ', $values);
            } elseif (!in_array($column->getType()->getValueType(), [TypeInterface::VALUE_TYPE_NUMBER])) {
                $result[$columnName] = null;
            } elseif ($column->getAggregator()->getType() == AggregatorInterface::TYPE_AVERAGE) {
                $result[$columnName] /= count($rows);
            } elseif ($column->getType()->getType() == TypeInterface::TYPE_PERCENT) {
                $result[$columnName] /= count($rows);
            } elseif ($column->getType()->getType() == TypeInterface::TYPE_PK && $column->getAggregator()->getType() == AggregatorInterface::TYPE_NONE) {
                $result[$columnName] = null;
            }
        }

        return $result;
    }

    private function buildQuery($select)
    {
        $queryString = $select->__toString();

        if (!$this->isUseCte() || !count($this->ctes)) {
            return $queryString;
        }

        return $this->getCtesPrefix() . ' ' . $queryString;
    }

    private function getCtesPrefix(): string
    {
        if (!$this->isUseCte() || !count($this->ctes)) {
            return '';
        }

        $cteParts = [];

        foreach ($this->ctes as $cteKey => $cte) {
            $alias = isset($cte['alias']) ? $cte['alias'] : $cteKey;
            $cteParts[] = sprintf('%s AS (%s)', $alias, $cte['select']->__toString());
        }

        return 'WITH ' . implode(', ', $cteParts);
    }
}
