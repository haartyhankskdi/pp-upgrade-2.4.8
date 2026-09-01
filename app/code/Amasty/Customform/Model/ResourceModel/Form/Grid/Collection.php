<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

namespace Amasty\Customform\Model\ResourceModel\Form\Grid;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\Config\Source\Status;
use Amasty\Customform\Model\ResourceModel\Answer;
use Amasty\Customform\Model\ResourceModel\Form\Collection as FormCollection;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

class Collection extends FormCollection implements SearchResultInterface
{
    protected $aggregations;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
        $model = Document::class
    ) {
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    protected function _construct(): void
    {
        $this->_setIdFieldName(FormInterface::FORM_ID);
        $this->addFilterToMap('form_id', 'main_table.form_id');
        $this->addFilterToMap('store_id', 'main_table.store_id');
        $this->addFilterToMap('results', 'main_table.answers_count');
    }

    protected function _initSelect()
    {
        $select = $this->getConnection()->select();
        $select->from(['subselect_table' => $this->getMainTable()]);
        $statusExpression = 'sum(case when answers.admin_response_status = \'%s\' then 1 else 0 end)';
        $select
            ->joinLeft(
                ['answers' => $this->getTable(Answer::TABLE_NAME)],
                'subselect_table.form_id=answers.form_id',
                'form_id as formId'
            )
            ->columns('COUNT(answers.form_id) as answers_count')
            ->columns(['answered_count' => new Zend_Db_Expr(sprintf($statusExpression, Status::ANSWERED))])
            ->columns(['pending_count' => new Zend_Db_Expr(sprintf($statusExpression, Status::PENDING))])
            ->group('subselect_table.form_id');

        $this->getSelect()->from(['main_table' => $select]);

        return $this;
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    public function getAllIds($limit = null, $offset = null)
    {
        return $this->getConnection()->fetchCol($this->_getAllIdsSelect($limit, $offset), $this->_bindParams);
    }

    public function getSearchCriteria()
    {
        return null;
    }

    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    public function setTotalCount($totalCount)
    {
        return $this;
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === FormInterface::STORE_ID) {
            $storeId = reset($condition);
            $condition = ['finset' => $storeId];
        }

        return parent::addFieldToFilter($field, $condition);
    }

    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * Create all ids retrieving select with limitation
     * Backward compatibility with EAV collection
     *
     * @param int $limit
     * @param int $offset
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    protected function _getAllIdsSelect($limit = null, $offset = null)
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $idsSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $idsSelect->columns($this->getResource()->getIdFieldName(), 'main_table');
        $idsSelect->limit($limit, $offset);

        return $idsSelect;
    }
}
