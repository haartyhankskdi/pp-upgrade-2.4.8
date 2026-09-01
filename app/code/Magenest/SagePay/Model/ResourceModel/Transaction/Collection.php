<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \Magenest\SagePay\Model\Transaction::class,
            \Magenest\SagePay\Model\ResourceModel\Transaction::class
        );
    }

    public function getTransactionGridData()
    {
        $this->getSelect()->joinLeft(
            [
                'secondTable' => $this->getTable('sales_order')
            ],
            'main_table.order_id = secondTable.entity_id',
            [
                'main_table.*',
                'secondTable.increment_id',
                'secondTable.status'
            ]
        );

        return $this;
    }
}
