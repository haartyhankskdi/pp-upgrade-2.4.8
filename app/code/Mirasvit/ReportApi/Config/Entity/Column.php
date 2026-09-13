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



namespace Mirasvit\ReportApi\Config\Entity;

use Magento\Framework\App\ResourceConnection;
use Mirasvit\Report\Api\Data\ReportInterface;
use Mirasvit\ReportApi\Api\Config\AggregatorInterface;
use Mirasvit\ReportApi\Api\Config\ColumnInterface;
use Mirasvit\ReportApi\Api\Config\FieldInterface;
use Mirasvit\ReportApi\Api\Config\SelectInterface;
use Mirasvit\ReportApi\Api\Config\TableInterface;
use Mirasvit\ReportApi\Api\Config\TypeInterface;
use Mirasvit\ReportApi\Config\Schema;
use Mirasvit\ReportApi\Service\NamingService;
use Mirasvit\ReportApi\Service\StoreResolver;

class Column implements ColumnInterface
{
    private $resource;

    private $name;

    private $table;

    private $tables = [];

    private $expression;

    private $storeExpression;

    private $storeResolver;

    private $label;

    private $fieldsPool = [];

    private $isUnique = false;

    private $type;

    private $aggregator;

    private $isInternal = false;

    public function __construct(
        ResourceConnection $resource,
        Schema $schema,
        TypeInterface $type,
        AggregatorInterface $aggregator,
        StoreResolver $storeResolver,
        $name,
        $data = []
    ) {
        $this->resource      = $resource;
        $this->storeResolver = $storeResolver;

        $this->name = $name;

        $this->type       = $type;
        $this->aggregator = $aggregator;

        $this->expression      = isset($data['expr']) ? $data['expr'] : '%1';
        $this->storeExpression = isset($data['store_expr']) ? $data['store_expr'] : null;

        $this->label = $data['label'];

        if (isset($data['uniq'])) {
            $this->isUnique = true;
        }

        if (isset($data['internal'])) {
            $this->isInternal = true;
        }

        $this->table = $data['table'];
        $this->table->addColumn($this);

        if (isset($data['tables'])) {
            $this->tables = $data['tables'];
        }

        if (isset($data['fields'])) {
            foreach ($data['fields'] as $field) {
                // in case table has column named like 'v.value'
                try {
                    $this->fieldsPool[] = $this->table->getField($field);
                    continue;
                } catch (\Exception $e) {
                    // intentional no-op: field name isn't a direct column, fall through to dotted table.column parse
                }

                $field = array_map('trim', explode('.', $field));
                if (count($field) == 1) {
                    $this->fieldsPool[] = $this->table->getField($field[0]);
                } else {
                    $table              = $schema->getTable($field[0]);
                    $this->fieldsPool[] = $table->getField($field[1]);
                }
            }
        }

        $this->label = NamingService::getLabel($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param TableInterface $table
     *
     * @return $this
     */
    public function setTable(TableInterface $table)
    {
        $this->table      = $table;
        $this->fieldsPool = [];
        $this->tables     = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregator()
    {
        return $this->aggregator;
    }

    /**
     * @param AggregatorInterface $aggregator
     *
     * @return $this
     */
    public function setAggregator(AggregatorInterface $aggregator)
    {
        $this->aggregator = $aggregator;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->isUnique || $this->getType()->getType() == TypeInterface::TYPE_PK;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isInternal()
    {
        return $this->isInternal;
    }

    /**
     * @param array $fields
     *
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fieldsPool = [];
        foreach ($fields as $field) {
            $this->fieldsPool[] = $this->table->getField($field);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->fieldsPool;
    }

    /**
     * @param mixed $expr
     *
     * @return $this
     */
    public function setExpression($expr)
    {
        $this->expression = $expr;

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * {@inheritdoc}
     */
    public function toDbExpr()
    {
        $applicableExpr = $this->getApplicableExpr();

        if ($this->table->isCte() || $this->table->isTmp()) {
            $fieldName = $this->name === 'entity_id' ? $this->name . '__value' : $this->name;
            $fieldExpr = $this->table->getName() . '.' . $fieldName;

            // Apply aggregator for TMP/CTE columns when main query groups by different dimension
            if ($this->aggregator->getType() !== AggregatorInterface::TYPE_NONE) {
                $expr = $this->aggregator->getExpression();
                return new \Zend_Db_Expr(str_replace('%1', $fieldExpr, $expr));
            }

            if (strpos($fieldExpr, '__cnt') !== false) {
                $fieldExpr = 'IFNULL(' . $fieldExpr . ', 0)';
            }

            return new \Zend_Db_Expr($fieldExpr);
        }

        $expr = (string)$this->aggregator->getExpression();
        $expr = str_replace('%1', $applicableExpr, $expr);

        if (strpos($expr, 'SELECT') !== false) {
            foreach ($this->tables as $table) {
                $tblName  = $table->getName();
                $realName = $this->resource->getTableName($tblName);

                if (!$realName || strpos($expr, $tblName) === false) {
                    continue;
                }

                $expr = str_replace(" {$tblName} ", " {$realName} " , $expr);
                $expr = str_replace(" {$tblName}.", " {$realName}. " , $expr);
            }
        }

        $idx = 1;
        foreach ($this->fieldsPool as $field) {
            $isTmp     = strrpos($field->getTable()->getName(), 'tmp') === 0;
            $fieldExpr = $field->getName() == 'entity_id' && (!$expr || $expr == '%1') && $isTmp
                ? $field->getName() . '__value'
                : $field->toDbExpr();

            $expr = str_replace('%' . $idx, $fieldExpr, $expr);
            $idx++;
        }

        return new \Zend_Db_Expr($expr);
    }

    private function getApplicableExpr(): string
    {
        if ($this->storeResolver->isResolved()
            && $this->storeExpression !== null
            && $this->type->getType() === TypeInterface::TYPE_MONEY
        ) {
            return $this->storeExpression;
        }

        if ($this->storeExpression !== null && strpos($this->expression, '{store_expr}') !== false) {
            return str_replace('{store_expr}', $this->storeExpression, $this->expression);
        }

        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function join(SelectInterface $select)
    {
        $isJoined = $select->joinTable($this->table);

        foreach ($this->tables as $tbl) {
            $isJoined = $select->joinTable($tbl) ? $isJoined : false;
        }

        return $isJoined;
    }

    /**
     * {@inheritdoc}
     */
    public function joinRight(SelectInterface $select)
    {
        $isJoined = $select->joinTable($this->table);

        foreach ($this->tables as $tbl) {
            $isJoined = $select->joinTable($tbl) ? $isJoined : false;
        }

        return $isJoined;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterOnly(ReportInterface $report)
    {
        if (in_array($this->getIdentifier(), $report->getAvailableFilters(), true)
            && !in_array($this->getIdentifier(), $report->getDefaultColumns(), true)
            && !in_array($this->getIdentifier(), $report->getColumns(), true)
            && !in_array($this->getIdentifier(), $report->getDimensions(), true)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return "{$this->table->getName()}|{$this->name}";
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->getIdentifier()}";
    }
}
