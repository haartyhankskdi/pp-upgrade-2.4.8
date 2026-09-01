<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\ResourceModel\Sales\Quote;

use Amasty\Reports\Model\Source\Quote\Status;
use Amasty\Reports\Traits\Filters;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;

class Collection extends \Magento\Quote\Model\ResourceModel\Quote\Collection
{
    use Filters;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Amasty\Reports\Helper\Data
     */
    private $helper;

    /**
     * @var Status
     */
    private $status;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Snapshot $entitySnapshot,
        \Magento\Framework\App\RequestInterface $request, // TODO move it out of here
        \Amasty\Reports\Helper\Data $helper,
        \Amasty\Reports\Model\Source\Quote\Status $status,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $entitySnapshot,
            $connection,
            $resource
        );
        $this->request = $request;
        $this->helper = $helper;
        $this->status = $status;
    }

    /**
     * @param Grid\Collection|\Amasty\Reports\Model\ResourceModel\Sales\Orders\Collection $collection
     */
    public function prepareCollection($collection)
    {
        $this->applyBaseFilters($collection);
        $this->applyToolbarFilters($collection);
    }

    /**
     * @param Grid\Collection|\Amasty\Reports\Model\ResourceModel\Sales\Orders\Collection $collection
     */
    private function applyBaseFilters($collection)
    {
        $this->joinAdvancedTables($collection);

        $collection->getSelect()
            ->reset(Select::COLUMNS)
            ->columns([
                'period' => 'amasty_quote.status',
                'total_orders' => 'COUNT(amasty_quote.quote_id)',
                'total_items' => 'SUM(main_table.items_count)',
                'subtotal' => 'SUM(main_table.base_subtotal)',
                'tax' => 'SUM(quote_item.tax)',
                'shipping' => 'SUM(quote_address.shipping)',
                'total' => 'SUM(main_table.base_grand_total)',
                'discounts' => '(IF(amasty_quote.sum_original_price, '
                    . 'SUM(custom_price - amasty_quote.sum_original_price), 0))',
            ])->group('amasty_quote.status');
    }

    /**
     * @param Grid\Collection|\Amasty\Reports\Model\ResourceModel\Sales\Orders\Collection $collection
     */
    private function applyToolbarFilters($collection)
    {
        $this->addFromFilter($collection);
        $this->addToFilter($collection);
        $this->addCurrentStoreFilter($collection);
    }

    /**
     * @param Grid\Collection|\Amasty\Reports\Model\ResourceModel\Sales\Orders\Collection $collection
     */
    private function joinAdvancedTables($collection)
    {
        $collection->getSelect()->joinLeft(
            ['amasty_quote' => $this->getTable('amasty_quote')],
            'main_table.entity_id = amasty_quote.quote_id',
            []
        )->joinLeft(
            ['quote_item' => $this->getQuoteItemsSelect()],
            'amasty_quote.quote_id = quote_item.quote_id',
            []
        )->joinLeft(
            ['quote_address' => $this->getQuoteAddressSelect()],
            'amasty_quote.quote_id = quote_address.quote_id',
            []
        )->where('amasty_quote.status IN (?)', $this->status->getVisibleOnFrontStatuses());
    }

    private function getQuoteItemsSelect(): \Magento\Framework\DB\Select
    {
        return $this->getConnection()->select()->from(
            $this->getTable('quote_item'),
            [
                'quote_id' => 'quote_id',
                'tax' => 'SUM(base_tax_amount)',
                'custom_price' => 'SUM(custom_price * qty)',
            ]
        )->group(
            'quote_id'
        );
    }

    private function getQuoteAddressSelect(): \Magento\Framework\DB\Select
    {
        return $this->getConnection()->select()->from(
            $this->getTable('quote_address'),
            [
                'quote_id' => 'quote_id',
                'shipping' => 'SUM(base_shipping_amount)',
            ]
        )->group(
            'quote_id'
        );
    }
}
