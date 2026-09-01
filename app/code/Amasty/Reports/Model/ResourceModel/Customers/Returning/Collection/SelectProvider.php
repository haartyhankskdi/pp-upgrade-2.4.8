<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Customers\Returning\Collection;

use Amasty\Reports\Model\Utilities\TimeZoneExpressionModifier;
use Magento\Framework\App\ResourceConnection;

class SelectProvider
{
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @var TimeZoneExpressionModifier
     */
    private $timeZoneExpressionModifier;

    public function __construct(
        ResourceConnection $connection,
        TimeZoneExpressionModifier $timeZoneExpressionModifier
    ) {
        $this->connection = $connection;
        $this->timeZoneExpressionModifier = $timeZoneExpressionModifier;
    }

    public function getNewCustomersQuery(): \Zend_Db_Expr
    {
        $orderTable = $this->connection->getTableName('sales_order');
        $createdAtExpression = $this->timeZoneExpressionModifier->execute($orderTable . '.created_at');
        return new \Zend_Db_Expr(
            "COUNT(distinct customer_email) -
            (SELECT COUNT(distinct customer_email) FROM " . $orderTable . "
            WHERE FIND_IN_SET(customer_email, GROUP_CONCAT(customerEmail))
            AND " . $createdAtExpression . " < created_date)"
        );
    }

    public function getReturningCustomersSelect(): string
    {
        return '(COUNT(entity_id) - (' . $this->getNewCustomersQuery() . '))';
    }

    public function getPercentSelect(): string
    {
        return '(ROUND((COUNT(entity_id) - (' . $this->getNewCustomersQuery() . ')) / COUNT(entity_id) * 100))';
    }
}
