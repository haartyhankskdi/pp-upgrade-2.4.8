<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Filters;

use Amasty\Reports\Model\Store;
use Magento\Framework\Data\Collection\AbstractDb;

class AddStoreFilter
{
    /**
     * @var Store
     */
    private $store;

    public function __construct(
        Store $store
    ) {
        $this->store = $store;
    }

    public function execute(AbstractDb $collection, $tablePrefix = 'main_table')
    {
        $store = $this->store->getCurrentStoreId();
        if ($store) {
            $collection->getSelect()->where($tablePrefix . '.store_id = ?', $store);
        }
    }
}
