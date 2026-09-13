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



namespace Mirasvit\ReportApi\Processor;

use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Mirasvit\ReportApi\Api\RequestInterface;
use Mirasvit\ReportApi\Config\Schema;

class Request extends AbstractSimpleObject implements RequestInterface
{
    const TABLE        = 'table';
    const COLUMNS      = 'columns';
    const FILTERS      = 'filters';
    const DIMENSIONS   = 'dimensions';
    const SORT_ORDERS  = 'sort_orders';
    const PAGE_SIZE    = 'page_size';
    const CURRENT_PAGE = 'current_page';
    const QUERY        = 'query';

    const CALCULATE_TOTALS = 'calculate_totals';

    private $serviceOutputProcessor;

    private $requestProcessor;

    private $schema;

    private $serializer;

    public function __construct(
        RequestProcessor $requestProcessor,
        ServiceOutputProcessor $serviceOutputProcessor,
        Schema $schema,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        array $data = []
    ) {
        $this->requestProcessor       = $requestProcessor;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->schema                 = $schema;
        $this->serializer             = $serializer;

        foreach ([self::COLUMNS, self::FILTERS, self::SORT_ORDERS] as $key) {
            $data[$key] = isset($data[$key]) ? $data[$key] : [];
        }

        parent::__construct($data);
    }

    /**
     * @param string $table
     *
     * @return RequestInterface|Request
     */
    public function setTable($table)
    {
        return $this->setData(self::TABLE, $table);
    }

    /**
     * @param array $columns
     *
     * @return RequestInterface|Request
     */
    public function setColumns(array $columns)
    {
        foreach ($columns as $idx => $column) {
            $columns[$idx] = $this->checkColumn($column);
        }

        return $this->setData(self::COLUMNS, $columns);
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return (string) $this->_get(self::TABLE);
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->_get(self::COLUMNS) ?? [];
    }

    /**
     * @param string $column
     *
     * @return RequestInterface|Request
     */
    public function addColumn($column)
    {
        return $this->addData(self::COLUMNS, [$column]);
    }

    /**
     * @param array $filters
     *
     * @return RequestInterface|Request
     */
    public function setFilters(array $filters)
    {
        foreach ($filters as $idx => $filter) {
            $filter->setColumn($this->checkColumn($filter->getColumn()));
        }

        return $this->setData(self::FILTERS, $filters);
    }

    /**
     * @return \Mirasvit\ReportApi\Api\Processor\FilterInterface[]
     */
    public function getFilters(): array
    {
        return $this->_get(self::FILTERS) ?? [];
    }

    /**
     * @param string       $column
     * @param array|string $value
     * @param string       $condition
     * @param string       $group
     *
     * @return RequestInterface|Request
     */
    public function addFilter($column, $value, $condition = 'eq', $group = '')
    {
        // filtering by concatenated data of select column can return unexpected results
        if (strpos($column, '__concat') !== false) {
            $c = $this->schema->getColumn($column);

            if ($c->getType()->getType() == 'select') {
                $column = str_replace('__concat', '', $column);
            }
        }

        return $this->addData(self::FILTERS, [new RequestFilter(
            $this->serializer,
            [
                RequestFilter::COLUMN         => $this->checkColumn($column),
                RequestFilter::VALUE          => $value,
                RequestFilter::CONDITION_TYPE => $condition,
                RequestFilter::GROUP          => $group,
            ])]);
    }

    /**
     * @param array $columns
     *
     * @return RequestInterface|Request
     */
    public function setDimensions($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $idx => $column) {
            $columns[$idx] = $this->checkColumn($column);
        }

        return $this->setData(self::DIMENSIONS, $columns);
    }

    /**
     * @return string[]
     */
    public function getDimensions(): array
    {
        return $this->_get(self::DIMENSIONS) ?: [];
    }

    /**
     * @param array $sortOrders
     *
     * @return RequestInterface|Request
     */
    public function setSortOrders(array $sortOrders)
    {
        return $this->setData(self::SORT_ORDERS, $sortOrders);
    }

    /**
     * @return \Mirasvit\ReportApi\Api\Processor\SortOrderInterface[]
     */
    public function getSortOrders(): array
    {
        return $this->_get(self::SORT_ORDERS) ?: [];
    }

    /**
     * @param string $column
     * @param string $direction
     *
     * @return RequestInterface|Request
     */
    public function addSortOrder($column, $direction)
    {
        return $this->addData(self::SORT_ORDERS, [new RequestSortOrder(
            $this->serializer,
            [
            RequestSortOrder::COLUMN    => $this->checkColumn($column),
            RequestSortOrder::DIRECTION => $direction,
        ])]);
    }

    /**
     * @param int $size
     *
     * @return RequestInterface|Request
     */
    public function setPageSize($size)
    {
        return $this->setData(self::PAGE_SIZE, $size);
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return (int) ($this->_get(self::PAGE_SIZE) ?: 10000000000);
    }

    /**
     * @param int $page
     *
     * @return RequestInterface|Request
     */
    public function setCurrentPage($page)
    {
        return $this->setData(self::CURRENT_PAGE, $page);
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return (int) ($this->_get(self::CURRENT_PAGE) ?: 1);
    }

    /**
     * @param string $query
     *
     * @return RequestInterface|Request
     */
    public function setQuery($query)
    {
        return $this->setData(self::QUERY, $query);
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return (string) $this->_get(self::QUERY);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return print_r($this->toArray(), true);
    }

    /**
     * @return array|object
     */
    public function toArray()
    {
        return $this->serviceOutputProcessor->convertValue($this, RequestInterface::class);
    }

    /**
     * @param bool $flag
     *
     * @return RequestInterface|Request
     */
    public function setCalculateTotals($flag)
    {
        return $this->setData(self::CALCULATE_TOTALS, (bool) $flag);
    }

    /**
     * @return bool
     */
    public function shouldCalculateTotals(): bool
    {
        $flag = $this->_get(self::CALCULATE_TOTALS);

        return $flag === null ? true : (bool) $flag;
    }

    /**
     * @return \Mirasvit\ReportApi\Api\ResponseInterface
     */
    public function process()
    {
        return $this->requestProcessor->process($this);
    }

    /**
     * @param string $identifier
     *
     * @return RequestInterface|Request
     */
    public function setIdentifier($identifier)
    {
        return $this->setData('identifier', $identifier);
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return (string) $this->_get('identifier');
    }

    /**
     * @param mixed $column
     *
     * @return string
     */
    private function checkColumn($column)
    {
        if (count(explode('|', $column)) == 1 && $this->getTable() && $column != 'pk') {
            $column = $this->getTable() . '|' . $column;
        }

        return $column;
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return Request
     */
    private function addData($key, $data)
    {
        return $this->setData($key, array_unique(array_merge_recursive(
            $this->_get($key),
            $data
        )));
    }
}
