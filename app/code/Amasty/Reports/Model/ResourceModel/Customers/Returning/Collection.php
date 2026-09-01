<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Customers\Returning;

use Amasty\Reports\Model\ResourceModel\Customers\Returning\Collection\SelectProvider;
use Amasty\Reports\Model\ResourceModel\Filters\AddFromFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddInterval;
use Amasty\Reports\Model\ResourceModel\Filters\AddStoreFilter;
use Amasty\Reports\Model\ResourceModel\Filters\AddToFilter;
use Amasty\Reports\Model\ResourceModel\Filters\RequestFiltersProvider;
use Amasty\Reports\Model\Utilities\CreateUniqueHash;
use Amasty\Reports\Model\Utilities\TimeZoneExpressionModifier;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\EntityFactory as EavEntityFactory;
use Magento\Eav\Model\ResourceModel\Helper as EavResourceHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DataObject\Copy\Config as DataObjectConfig;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Customer\Model\ResourceModel\Customer\Collection
{
    /**
     * @var AddFromFilter
     */
    private $addFromFilter;

    /**
     * @var AddToFilter
     */
    private $addToFilter;

    /**
     * @var AddStoreFilter
     */
    private $addStoreFilter;

    /**
     * @var AddInterval
     */
    private $addInterval;

    /**
     * @var RequestFiltersProvider
     */
    private $filtersProvider;

    /**
     * @var TimeZoneExpressionModifier
     */
    private $timeZoneExpressionModifier;

    /**
     * @var CreateUniqueHash
     */
    private $createUniqueHash;

    /**
     * @var SelectProvider
     */
    private $selectProvider;

    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        EavConfig $eavConfig,
        ResourceConnection $resource,
        EavEntityFactory $eavEntityFactory,
        EavResourceHelper $resourceHelper,
        UniversalFactory $universalFactory,
        Snapshot $entitySnapshot,
        DataObjectConfig $fieldsetConfig,
        SelectProvider $selectProvider,
        AddFromFilter $addFromFilter,
        AddToFilter $addToFilter,
        AddStoreFilter $addStoreFilter,
        AddInterval $addInterval,
        RequestFiltersProvider $filtersProvider,
        TimeZoneExpressionModifier $timeZoneExpressionModifier,
        CreateUniqueHash $createUniqueHash,
        AdapterInterface $connection = null,
        $modelName = self::CUSTOMER_MODEL_NAME
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $entitySnapshot,
            $fieldsetConfig,
            $connection,
            $modelName
        );

        $this->addFromFilter = $addFromFilter;
        $this->addToFilter = $addToFilter;
        $this->addStoreFilter = $addStoreFilter;
        $this->addInterval = $addInterval;
        $this->filtersProvider = $filtersProvider;
        $this->timeZoneExpressionModifier = $timeZoneExpressionModifier;
        $this->selectProvider = $selectProvider;
        $this->createUniqueHash = $createUniqueHash;
    }

    /**
     * @return string
     */
    public function getMainTable()
    {
        return $this->getTable('sales_order');
    }

    public function prepareCollection(AbstractDb $collection): void
    {
        $this->applyBaseFilters($collection);
        $this->applyToolbarFilters($collection);
    }

    private function applyBaseFilters(AbstractDb $collection): void
    {
        $collection->getSelect()->reset(Select::FROM);
        $collection->getSelect()->from(['main_table' => $this->getTable('sales_order')]);
        $collection->getSelect()->reset(Select::COLUMNS);
        $collection->getSelect()->reset(Select::GROUP);

        $this->addInterval->execute($collection);

        $createdAtExpression = $this->timeZoneExpressionModifier->execute('created_at');

        $collection->getSelect()
            ->columns([
                'customerEmail' => 'customer_email',
                'count' => 'COUNT(entity_id)',
                'created_date' => 'DATE(' . $createdAtExpression . ')',
                'new_customers' => '(' . $this->selectProvider->getNewCustomersQuery() . ')',
                'entity_id' => 'CONCAT(entity_id,\'' . $this->createUniqueHash->execute() . '\')',
                'returning_customers' => $this->selectProvider->getReturningCustomersSelect(),
                'percent' => $this->selectProvider->getPercentSelect()
            ]);

        $collection->getSelect()->order("DATE(created_at) DESC");
    }

    private function applyToolbarFilters(AbstractDb $collection): void
    {
        $this->addFromFilter->execute($collection);
        $this->addToFilter->execute($collection);
        $this->addStoreFilter->execute($collection);
    }
}
