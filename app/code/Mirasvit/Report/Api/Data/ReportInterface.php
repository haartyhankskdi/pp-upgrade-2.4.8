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
 * @package   mirasvit/module-report
 * @version   1.4.74
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Report\Api\Data;

interface ReportInterface extends ReportApiInterface
{
    const TABLE = 'table';

    const COLUMNS            = 'columns';
    const DIMENSIONS         = 'dimensions';
    const INTERNAL_COLUMNS   = 'internal_columns';
    const INTERNAL_FILTERS   = 'internal_filters';
    const FILTERS            = 'filters';
    const PRIMARY_FILTERS    = 'primary_filters';
    const PRIMARY_DIMENSIONS = 'primary_dimensions';
    const SORT_ORDERS        = 'sort_orders';
    const PAGE_SIZE          = 'page_size';
    const TIME_RANGE         = 'time_range';
    const REPORT_IDENTIFIER  = 'report_identifier';
    const SHARE_ENABLED      = 'share_enabled';
    const SHARE_IDENTIFIER   = 'share_identifier';
    const IS_EDIT_DISABLED   = 'isEditable';

    const GRID_CONFIG  = 'grid_config';
    const CHART_CONFIG = 'chart_config';

    const IS_CUSTOMIZED = 'is_customized';

    /**
     * @return $this
     */
    public function init();

    /**
     * @param string $tableName
     *
     * @return $this
     */
    public function setTable($tableName);

    /**
     * @param string[] $columns
     *
     * @return $this
     */
    public function setColumns(array $columns);

    /**
     * @param string[] $columns
     *
     * @return $this
     */
    public function setDimensions(array $columns);

    /**
     * @param string[] $columns
     *
     * @return $this
     */
    public function setInternalColumns(array $columns);

    /**
     * Accepts FilterInterface objects or raw filter config arrays; getInternalFilters() normalizes both.
     *
     * @param \Mirasvit\ReportApi\Api\Processor\FilterInterface[]|array[] $filters
     *
     * @return $this
     */
    public function setInternalFilters(array $filters);

    /**
     * @param string[] $columns
     *
     * @return $this
     */
    public function setPrimaryDimensions(array $columns);

    /**
     * Accepts FilterInterface objects or raw filter config arrays; getFilters() normalizes both.
     *
     * @param \Mirasvit\ReportApi\Api\Processor\FilterInterface[]|array[] $filters
     *
     * @return $this
     */
    public function setFilters(array $filters);

    /**
     * @param string[] $columns
     *
     * @return $this
     */
    public function setPrimaryFilters(array $columns);

    /**
     * @param \Mirasvit\ReportApi\Api\Processor\SortOrderInterface[] $orders
     *
     * @return $this
     */
    public function setSortOrders(array $orders): self;

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setIsSharingEnabled(bool $value): self;

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setShareIdentifier(string $value): self;

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setReportIdentifier(string $value): self;

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setIsCustomized(bool $value): self;

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setTimeRange(string $value): self;

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setDateRange(string $value): self;

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setPageSize(int $value): self;

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setIsEditDisabled(bool $value): self;
}
