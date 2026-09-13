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


declare(strict_types=1);

namespace Mirasvit\Report\Api\Data;

/**
 * Read-only interface for report metadata exposed via REST API.
 *
 * Magento's REST serializer uses the declared return type to decide which
 * getters to serialize, so GridConfig, ChartConfig, setters, and init()
 * are invisible in the response when this interface is used.
 *
 * @api
 */
interface ReportApiInterface
{
    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getTable();

    /**
     * @return string[]
     */
    public function getColumns();

    /**
     * @return string[]
     */
    public function getDimensions();

    /**
     * @return string[]
     */
    public function getInternalColumns();

    /**
     * @return \Mirasvit\ReportApi\Api\Processor\FilterInterface[]
     */
    public function getInternalFilters();

    /**
     * @return string[]
     */
    public function getPrimaryDimensions();

    /**
     * @return \Mirasvit\ReportApi\Api\Processor\FilterInterface[]
     */
    public function getFilters();

    /**
     * @return string[]
     */
    public function getPrimaryFilters();

    /**
     * @return \Mirasvit\ReportApi\Api\Processor\SortOrderInterface[]
     */
    public function getSortOrders(): array;

    /**
     * @return bool
     */
    public function getIsSharingEnabled(): bool;

    /**
     * @return string|null
     */
    public function getShareIdentifier(): ?string;

    /**
     * @return string|null
     */
    public function getReportIdentifier(): ?string;

    /**
     * @return bool
     */
    public function getIsCustomized(): bool;

    /**
     * @return string|null
     */
    public function getTimeRange(): ?string;

    /**
     * @return string|null
     */
    public function getDateRange(): ?string;

    /**
     * @return int
     */
    public function getPageSize(): int;

    /**
     * @return bool
     */
    public function getIsEditDisabled(): bool;
}
