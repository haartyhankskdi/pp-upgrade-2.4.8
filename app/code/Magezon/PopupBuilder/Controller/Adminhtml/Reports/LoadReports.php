<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Controller\Adminhtml\Reports;

class LoadReports extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magezon_PopupBuilder::report';

    /**
     * @var \Magezon\PopupBuilder\Model\ReportManager
     */
    protected $reportManager;

    /**
     * @param \Magento\Backend\App\Action\Context       $context
     * @param \Magezon\PopupBuilder\Model\ReportManager $reportManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magezon\PopupBuilder\Model\ReportManager $reportManager
    ) {
        parent::__construct($context);
        $this->reportManager = $reportManager;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $result['status'] = true;
        $params = $this->getRequest()->getParams();
        if (isset($params['item_id'])) {
            $storeId = is_numeric($params['store_id']) ? (int) $params['store_id'] : null;
            $this->prepareViews($result, $params['item_id'], $params['from'], $params['to'], $storeId);
            $this->prepareClicks($result, $params['item_id'], $params['from'], $params['to'], $storeId);
            $this->prepareCloses($result, $params['item_id'], $params['from'], $params['to'], $storeId);
            if ($result['totalViews']) {
                $conversion = number_format(($result['totalClicks'] / $result['totalViews']) * 100, 2) . '%';
            } else {
                $conversion = '0%';
            }
            $result['conversion'] = $conversion;
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
    }

    /**
     * @param  array &$result
     * @param  integer $id
     * @param  string $from
     * @param  string $to
     * @param  integer|null $storeId
     */
    protected function prepareViews(&$result, $id, $from, $to, $storeId)
    {
        $report = $this->reportManager->getChartData('open', $id, $from, $to, $storeId);
        $result['labels'] = $report['labels'];
        $total = 0;
        foreach ($report['items'] as $item) {
            $total += $item;
        }
        $result['views']      = $report['items'];
        $result['totalViews'] = $total;
    }

    /**
     * @param  array &$result
     * @param  integer $id
     * @param  string $from
     * @param  string $to
     * @param  integer|null $storeId
     */
    protected function prepareClicks(&$result, $id, $from, $to, $storeId)
    {
        $report = $this->reportManager->getChartData('click', $id, $from, $to, $storeId);
        $total  = 0;
        foreach ($report['items'] as $item) {
            $total += $item;
        }
        $result['clicks'] = $report['items'];
        $result['totalClicks'] = $total;
    }

    /**
     * @param  array &$result
     * @param  integer $id
     * @param  string $from
     * @param  string $to
     * @param  integer|null $storeId
     */
    protected function prepareCloses(&$result, $id, $from, $to, $storeId)
    {
        $report = $this->reportManager->getChartData('close', $id, $from, $to, $storeId);
        $total  = 0;
        foreach ($report['items'] as $item) {
            $total += $item;
        }
        $result['closes'] = $report['items'];
        $result['totalCloses'] = $total;
    }
}
