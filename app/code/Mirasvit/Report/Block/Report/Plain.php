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


namespace Mirasvit\Report\Block\Report;


use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Mirasvit\Core\Service\DegradationReporter;
use Mirasvit\ReportApi\Api\Processor\ResponseColumnInterface;
use Mirasvit\ReportApi\Api\Processor\ResponseItemInterface;
use Mirasvit\ReportApi\Config\Schema;

class Plain extends Template
{
    protected $_template = "Mirasvit_Report::report/plain.phtml";

    private $registry;

    private $schema;

    private $header = [];

    private $footer = [];

    private $rows = [];

    private $filters = [];

    private $columnTypes = [];

    private $degradationReporter;

    public function __construct(
        Schema $schema,
        Registry $registry,
        DegradationReporter $degradationReporter,
        Template\Context $context,
        array $data = []
    ) {
        $this->schema              = $schema;
        $this->registry            = $registry;
        $this->degradationReporter = $degradationReporter;

        parent::__construct($context, $data);
    }

    public function getReport()
    {
        return $this->registry->registry('current_report');
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function getFooter(): array
    {
        return $this->footer;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getColumnTypes(): array
    {
        return $this->columnTypes;
    }

    public function buildReportContent(): bool
    {
        $request = $this->registry->registry('current_request');

        if (!$request) {
            return false;
        }

        return $this->buildReport($request);
    }

    private function buildReport($request)
    {
        $response = $request->process();

        $rows = [];
        foreach ($response->getColumns() as $column) {
            $this->header[] = $column->getLabel();
            $rows['header'][] = $column->getLabel();

            try {
                $schemaColumn = $this->schema->getColumn($column->getName());
                $this->columnTypes[] = $schemaColumn->getType()->getType();
            } catch (\Exception $e) {
                $this->degradationReporter->report('report', 'schema.column_type', 'failed to resolve schema column type for "' . $column->getName() . '": ' . $e->getMessage());
                $this->columnTypes[] = '';
            }
        }

        foreach ($response->getItems() as $item) {
            $this->addRow($this->rows, $item, $response->getColumns());
        }

        foreach ($response->getTotals()->getFormattedData() as $key => $value) {
            $this->footer[] = $value;
            $rows['footer'][] = $value;
        }

        foreach ($request->getFilters() as $filter) {
            $value = $filter->getValue();

            $this->filters[] = [
                'column'   => $this->schema->getColumn($filter->getColumn())->getLabel(),
                'operator' => $this->getOperatorLabel($filter->getConditionType()),
                'value'    => (is_array($value) ? implode(', ', $value) : $value),
            ];
        }

        return true;
    }

    private function addRow(&$rows, ResponseItemInterface $item, array $columns)
    {
        $formattedData = $item->getFormattedData();

        $data = [];
        /** @var ResponseColumnInterface $column */
        foreach ($columns as $column) {
            $name = $column->getName();

            if (isset($formattedData[$name])) {
                $data[] = $formattedData[$name];
            } else {
                $data[] = '';
            }
        }

        $rows[] = $data;

        foreach ($item->getItems() as $subItem) {
            $this->addRow($rows, $subItem, $columns);
        }
    }

    private function getOperatorLabel(string $operator): string
    {
        $map = [
            'like'  => 'contains',
            'nlike' => 'does not contains',
            'in'    => 'is one of',
            'nin'   => 'is not one of',
            'gt'    => 'is greater than',
            'lt'    => 'is less than',
            'gteq'  => 'is greater than or equal to',
            'lteq'  => 'is less than or equal to',
            'eq'    => 'is',
            'neq'   => 'is not',
        ];

        return isset($map[$operator]) ? $map[$operator] : $operator;
    }

    protected function _toHtml()
    {
        if ($this->buildReportContent()) {
            return parent::_toHtml();
        } else {
            return (string)$this->registry->registry('current_message')
                ?: (string)__('Something went wrong while building the report');
        }
    }
}
