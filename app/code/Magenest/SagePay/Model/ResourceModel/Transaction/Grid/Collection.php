<?php
/**
 * Created by PhpStorm.
 * User: duchai
 * Date: 20/03/2019
 * Time: 08:44
 */
namespace Magenest\SagePay\Model\ResourceModel\Transaction\Grid;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{

    const ORDER_GRID_FIELDS = ['increment_id','customer_id','status'];

    public function _renderFiltersBefore()
    {
        $this->getSelect()->joinLeft(
            ['order_grid' => $this->getTable('sales_order_grid')],
            'main_table.order_id = order_grid.entity_id',
            self::ORDER_GRID_FIELDS
        );
        parent::_renderFiltersBefore();
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if (in_array($field, self::ORDER_GRID_FIELDS)) {
            $field = "order_grid.$field";
        } else {
            $field = "main_table.$field";
        }
        return parent::addFieldToFilter($field, $condition);
    }

    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        if (in_array($field, self::ORDER_GRID_FIELDS)) {
            $field = "order_grid.$field";
        } else {
            $field = "main_table.$field";
        }
        return parent::setOrder($field, $direction);
    }
}
