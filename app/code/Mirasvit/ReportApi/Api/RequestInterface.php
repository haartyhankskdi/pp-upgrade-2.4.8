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



namespace Mirasvit\ReportApi\Api;


interface RequestInterface
{
    /**
     * @return string
     */
    public function getTable(): string;

    /**
     * @return string[]
     */
    public function getColumns(): array;

    /**
     * @return \Mirasvit\ReportApi\Api\Processor\FilterInterface[]
     */
    public function getFilters(): array;

    /**
     * @return string[]
     */
    public function getDimensions(): array;

    /**
     * @return \Mirasvit\ReportApi\Api\Processor\SortOrderInterface[]
     */
    public function getSortOrders(): array;

    /**
     * @return int
     */
    public function getPageSize(): int;

    /**
     * @return int
     */
    public function getCurrentPage(): int;

    /**
     * @return string
     */
    public function getQuery(): string;

    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @param string $table
     *
     * @return $this
     */
    public function setTable($table);

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns(array $columns);

    /**
     * @param string $column
     *
     * @return $this
     */
    public function addColumn($column);

    /**
     * @param \Mirasvit\ReportApi\Api\Processor\FilterInterface[] $filters
     *
     * @return $this
     */
    public function setFilters(array $filters);

    /**
     * @param string       $column
     * @param array|string $value
     * @param string       $condition
     * @param string       $group
     *
     * @return $this
     */
    public function addFilter($column, $value, $condition = 'eq', $group = '');

    /**
     * @param array|string $columns
     *
     * @return $this
     */
    public function setDimensions($columns);

    /**
     * @param array $sortOrders
     *
     * @return $this
     */
    public function setSortOrders(array $sortOrders);

    /**
     * @param string $column
     * @param string $direction
     *
     * @return $this
     */
    public function addSortOrder($column, $direction);

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setPageSize($size);

    /**
     * @param int $page
     *
     * @return $this
     */
    public function setCurrentPage($page);

    /**
     * @param string $query
     *
     * @return $this
     */
    public function setQuery($query);

    /**
     * @param string $identifier
     *
     * @return $this
     */
    public function setIdentifier($identifier);

    /**
     * Whether the response should compute grand totals for this request.
     * Totals are page-independent, so a paginated consumer (e.g. CSV export) can
     * compute them once and disable them on subsequent pages to avoid re-running
     * the full grouped totals query per page. Defaults to true.
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function setCalculateTotals($flag);

    /**
     * @return bool
     */
    public function shouldCalculateTotals(): bool;

    /**
     * @return \Mirasvit\ReportApi\Api\ResponseInterface
     */
    public function process();
}
