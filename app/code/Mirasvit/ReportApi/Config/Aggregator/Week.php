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



namespace Mirasvit\ReportApi\Config\Aggregator;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Mirasvit\ReportApi\Api\Config\AggregatorInterface;

class Week implements AggregatorInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    private $scopeConfig;

    /**
     * Week constructor.
     *
     * @param ResourceConnection $resource
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource
    ) {
        $this->resource    = $resource;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE_WEEK;
    }

    /**
     * @return \Zend_Db_Expr
     */
    public function getExpression()
    {
        $connection   = $this->resource->getConnection();
        // https://dev.mysql.com/doc/refman/8.4/en/date-and-time-functions.html#function_week
        $yearWeekMode = ((int)$this->scopeConfig->getValue('general/locale/firstday') > 0 ? 1 : 4);
        $yearWeek     = new \Zend_Db_Expr('YEARWEEK(%1, ' . $yearWeekMode . ')');

        return $yearWeek;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Week';
    }
}
