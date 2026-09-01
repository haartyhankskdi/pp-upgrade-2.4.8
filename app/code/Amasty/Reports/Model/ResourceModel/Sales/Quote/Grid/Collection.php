<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Sales\Quote\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        \Amasty\Reports\Model\ResourceModel\Sales\Quote\Collection $filterApplier,
        $mainTable = 'quote',
        $resourceModel = \Amasty\Reports\Model\ResourceModel\Sales\Quote\Collection::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);

        $filterApplier->prepareCollection($this);
    }
}
