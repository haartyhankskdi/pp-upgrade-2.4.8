<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Utilities;

use Amasty\Reports\Model\ResourceModel\Filters\RequestFiltersProvider;
use Amasty\Reports\Model\ResourceModel\RuleIndex;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Store\Model\Store;

class AddReportRules
{
    /**
     * @var RequestFiltersProvider
     */
    private $filtersProvider;

    /**
     * @var RuleIndex
     */
    private $ruleIndex;

    public function __construct(RequestFiltersProvider $filtersProvider, RuleIndex $ruleIndex)
    {
        $this->filtersProvider = $filtersProvider;
        $this->ruleIndex = $ruleIndex;
    }

    public function execute(AbstractDb $collection): void
    {
        $filters = $this->filtersProvider->execute();
        if (isset($filters['rule']) && $filters['rule']) {
            $storeId = isset($filters['store']) ? (int)$filters['store'] : Store::DEFAULT_STORE_ID;
            $productIds = $this->ruleIndex->getAppliedProducts(
                (int) $filters['rule'],
                $storeId
            );
            $collection->getSelect()->where('sales_order_item.product_id in (?)', $productIds);
        }
    }
}
