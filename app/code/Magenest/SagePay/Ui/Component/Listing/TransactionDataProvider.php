<?php

/**
 * Created by PhpStorm.
 * User: doanhcn2
 * Date: 07/09/2019
 * Time: 16:31
 */


namespace Magenest\SagePay\Ui\Component\Listing;

use phpDocumentor\Reflection\Types\This;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;

class TransactionDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $_collectionFactory;

    /**
     * @var ReportingInterface
     */
    protected $reporting;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SearchCriteria
     */
    protected $searchCriteria;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        \Magenest\SagePay\Model\ResourceModel\Transaction\CollectionFactory $_transactionCollection,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->reporting = $reporting;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_collectionFactory = $_transactionCollection;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getCollection()
    {
        if ($this->collection == null) {
            $this->collection = $this->_collectionFactory->create()->getTransactionGridData();
        }
        return $this->collection;
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'increment_id' && strtolower($filter->getValue()) == '%error%') {
            $filter->setConditionType('null');
            $filter->setField('main_table.order_id');
            $filter->setValue(true);
        }
        if ($filter->getField() == 'customer_id') {
            $filter->setField('main_table.customer_id');
            if (strtolower($filter->getValue()) == '%guest%') {
                $filter->setValue(true);
                $filter->setConditionType('null');
            }
        }
        if ($filter->getField() == 'order_status') {
            $filter->setField('secondTable.status');
        }

        if ($filter->getField() == 'customer_email') {
            $filter->setField('main_table.customer_email');
        }
        parent::addFilter($filter);
    }

    public function getSearchCriteria()
    {
        if (!$this->searchCriteria) {
            $this->searchCriteria = $this->searchCriteriaBuilder->create();
            $this->searchCriteria->setRequestName($this->name);
        }
        return $this->searchCriteria;
    }

    public function getSearchResult()
    {
        return $this->reporting->search($this->getSearchCriteria());
    }
}
