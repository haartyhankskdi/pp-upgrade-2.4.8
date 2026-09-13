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



namespace Mirasvit\Report\Model;

use Magento\Framework\Api\AbstractSimpleObject;
use Mirasvit\Report\Api\Data\ReportInterface;
use Mirasvit\ReportApi\Api\RequestInterface;
use Mirasvit\ReportApi\Processor\RequestFilterFactory;
use Mirasvit\ReportApi\Processor\RequestSortOrderFactory;
use Mirasvit\ReportApi\Processor\ResponseItem;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
abstract class AbstractReport extends AbstractSimpleObject implements ReportInterface
{
    protected $context;

    protected $provider;

    /**
     * @var int
     */
    private $version = 2;

    /**
     * @var RequestFilterFactory
     */
    private $requestFilterFactory;

    /**
     * @var RequestSortOrderFactory
     */
    private $requestSortOrderFactory;

    public function __construct(
        Context                  $context,
        ?RequestFilterFactory    $requestFilterFactory = null,
        ?RequestSortOrderFactory $requestSortOrderFactory = null
    ) {
        $this->context                 = $context;
        $this->provider                = $this->context->getProvider();
        if ($requestFilterFactory === null) {
            /** @var RequestFilterFactory $requestFilterFactory */
            $requestFilterFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(RequestFilterFactory::class);
        }

        if ($requestSortOrderFactory === null) {
            /** @var RequestSortOrderFactory $requestSortOrderFactory */
            $requestSortOrderFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(RequestSortOrderFactory::class);
        }

        $this->requestFilterFactory    = $requestFilterFactory;
        $this->requestSortOrderFactory = $requestSortOrderFactory;

        parent::__construct([
            self::COLUMNS            => [],
            self::DIMENSIONS         => [],
            self::INTERNAL_COLUMNS   => [],
            self::INTERNAL_FILTERS   => [],
            self::PRIMARY_DIMENSIONS => [],
            self::PRIMARY_FILTERS    => [],
            self::GRID_CONFIG        => new GridConfig(),
            self::CHART_CONFIG       => new ChartConfig(),
            self::SORT_ORDERS        => []
        ]);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        $code = str_replace('Mirasvit\Reports\Reports\\', '', get_class($this));

        return strtolower(str_replace(['\Interceptor', '\\'], ['', '_'], $code));
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        return $this->_get(self::TABLE);
    }

    /**
     * {@inheritdoc}
     */
    public function setTable($tableName)
    {
        return $this->setData(self::TABLE, $tableName);
    }

    /** STATE */

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return $this->_get(self::COLUMNS);
    }

    /**
     * {@inheritdoc}
     */
    public function setColumns(array $columns)
    {
        return $this->setData(self::COLUMNS, array_values($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function getDimensions()
    {
        return $this->_get(self::DIMENSIONS);
    }

    /**
     * {@inheritdoc}
     */
    public function setDimensions(array $columns)
    {
        if ($this->version == 1) {
            return $this->setData(self::PRIMARY_DIMENSIONS, array_values($columns));
        }

        return $this->setData(self::DIMENSIONS, array_values($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function getInternalColumns()
    {
        return $this->_get(self::INTERNAL_COLUMNS);
    }

    /**
     * {@inheritdoc}
     */
    public function setInternalColumns(array $columns)
    {
        return $this->setData(self::INTERNAL_COLUMNS, array_values($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function getInternalFilters()
    {
        $items = $this->_get(self::INTERNAL_FILTERS) ?: [];
        $result = [];
        foreach ($items as $item) {
            if ($item instanceof \Mirasvit\ReportApi\Api\Processor\FilterInterface) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $result[] = $this->requestFilterFactory->create(['data' => $item]);
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setInternalFilters(array $filters)
    {
        return $this->setData(self::INTERNAL_FILTERS, array_values($filters));
    }

    /** SCHEMA */

    /**
     * {@inheritdoc}
     */
    public function getPrimaryDimensions()
    {
        return $this->_get(self::PRIMARY_DIMENSIONS);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrimaryDimensions(array $columns)
    {
        return $this->setData(self::PRIMARY_DIMENSIONS, array_values($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $items = $this->_get(self::FILTERS) ?: [];
        $result = [];
        foreach ($items as $item) {
            if ($item instanceof \Mirasvit\ReportApi\Api\Processor\FilterInterface) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $result[] = $this->requestFilterFactory->create(['data' => $item]);
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilters(array $filters)
    {
        return $this->setData(self::FILTERS, array_values($filters));
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryFilters()
    {
        return $this->_get(self::PRIMARY_FILTERS);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrimaryFilters(array $columns)
    {
        return $this->setData(self::PRIMARY_FILTERS, $columns);
    }

    /**
     * @return \Mirasvit\Report\Model\GridConfig
     */
    public function getGridConfig()
    {
        return $this->_get(self::GRID_CONFIG);
    }

    /**
     * @return \Mirasvit\Report\Model\ChartConfig
     */
    public function getChartConfig()
    {
        return $this->_get(self::CHART_CONFIG);
    }

    /**
     * @param ResponseItem     $item
     * @param RequestInterface $request
     *
     * @return array
     */
    public function getActions(ResponseItem $item, RequestInterface $request)
    {
        return [];
    }

    /**
     * @param string $report
     * @param array  $filters
     *
     * @return string
     */
    public function getReportUrl($report, $filters = [])
    {
        return $this->context->urlManager->getUrl(
            'reports/report/view',
            [
                'report' => $report,
                '_query' => [
                    'filters' => $filters,
                ],
            ]
        );
    }

    /**
     * @param array $columns
     *
     * @return $this
     * @deprecated
     */
    public function addFastFilters($columns)
    {
        $this->version = 1;

        $columns = array_merge($this->getPrimaryFilters(), $columns);
        $columns = array_unique($columns);

        return $this->setPrimaryFilters($columns);
    }

    /**
     * @param mixed $columns
     *
     * @return $this
     * @deprecated
     */
    public function addColumns($columns)
    {
        $this->version = 1;

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return $this
     * @deprecated
     */
    public function addDimensions($columns)
    {
        $this->version = 1;

        $columns = array_merge($this->getPrimaryDimensions(), $columns);
        $columns = array_unique($columns);

        return $this->setPrimaryDimensions($columns);
    }


    /**
     * @param array $columns
     *
     * @return $this
     * @deprecated
     */
    public function setDefaultColumns($columns)
    {
        $this->version = 1;

        return $this->setColumns($columns);
    }

    /**
     * @param string $column
     *
     * @return $this
     * @deprecated
     */
    public function setDefaultDimension($column)
    {
        $this->version = 1;

        return $this->setData(self::DIMENSIONS, [$column]);
    }

    /**
     * @param array $filters
     *
     * @return $this
     * @deprecated
     */
    public function setDefaultFilters($filters)
    {
        return $this->setInternalFilters($filters);
    }

    /**
     * @param array $columns
     *
     * @return $this
     * @deprecated
     */
    public function setRequiredColumns($columns)
    {
        return $this->setInternalColumns($columns);
    }

    /**
     * @param array $columns
     *
     * @return $this
     * @deprecated
     */
    public function addDefaultColumns($columns)
    {
        $this->version = 1;

        return $this->setColumns($columns);
    }

    /**
     * @param array $columns
     *
     * @return $this
     * @deprecated
     */
    public function addAvailableFilters($columns)
    {
        return $this;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getAllColumns()
    {
        return [];
    }

    public function getSortOrders(): array
    {
        $items = $this->_get(self::SORT_ORDERS) ?: [];
        $result = [];
        foreach ($items as $item) {
            if ($item instanceof \Mirasvit\ReportApi\Api\Processor\SortOrderInterface) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $result[] = $this->requestSortOrderFactory->create(['data' => $item]);
            }
        }
        return $result;
    }

    public function setSortOrders(array $orders): ReportInterface
    {
        return $this->setData(self::SORT_ORDERS, $orders);
    }

    public function getIsSharingEnabled(): bool
    {
        return (bool)$this->_get(self::SHARE_ENABLED);
    }

    public function setIsSharingEnabled(bool $value): ReportInterface
    {
        return $this->setData(self::SHARE_ENABLED, $value);
    }

    public function getShareIdentifier(): ?string
    {
        return $this->_get(self::SHARE_IDENTIFIER) ?: null;
    }

    public function setShareIdentifier(?string $value = null): ReportInterface
    {
       return $this->setData(self::SHARE_IDENTIFIER, $value);
    }

    public function getReportIdentifier(): ?string
    {
        return $this->_get(self::REPORT_IDENTIFIER) ?: null;
    }

    public function setReportIdentifier(?string $value = null): ReportInterface
    {
        return $this->setData(self::REPORT_IDENTIFIER, $value);
    }

    public function getIsCustomized(): bool
    {
        return (bool)$this->_get(self::IS_CUSTOMIZED);
    }

    public function setIsCustomized(bool $value): ReportInterface
    {
        return $this->setData(self::IS_CUSTOMIZED, $value);
    }

    public function setPageSize(int $value): ReportInterface
    {
        return $this->setData(self::PAGE_SIZE, $value);
    }

    public function getPageSize(): int
    {
        return $this->_get(self::PAGE_SIZE) ? (int)$this->_get(self::PAGE_SIZE) : 20;
    }

    public function getTimeRange(): ?string
    {
        return $this->_get(self::TIME_RANGE);
    }

    public function setTimeRange(string $value): ReportInterface
    {
        return $this->setData(self::TIME_RANGE, $value);
    }

    public function getDateRange(): ?string
    {
        return $this->getTimeRange();
    }

    public function setDateRange(string $value): ReportInterface
    {
        return $this->setTimeRange($value);
    }

    public function getIsEditDisabled(): bool
    {
        return (bool)$this->_get(self::IS_EDIT_DISABLED);
    }

    public function setIsEditDisabled(bool $value): ReportInterface
    {
        return $this->setData(self::IS_EDIT_DISABLED, $value);
    }
}
