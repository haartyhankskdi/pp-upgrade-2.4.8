<?php

namespace Amasty\Reports\Model\ResourceModel\Customers\Customers\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * @var \Amasty\Reports\Model\ResourceModel\Customers\Customers\Collection
     */
    private $filterApplier;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        \Amasty\Reports\Model\ResourceModel\Customers\Customers\Collection $filterApplier,
        $mainTable = '',
        $resourceModel = \Amasty\Reports\Model\ResourceModel\Customers\Customers\Collection::class
    ) {
        $this->filterApplier = $filterApplier;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);

        $this->filterApplier->prepareCollection($this, 'main_table');
    }

    /**
     * @return string
     */
    public function getMainTable()
    {
        return $this->filterApplier->getMainTable();
    }
}
