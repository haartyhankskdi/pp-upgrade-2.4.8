<?php

/**
 * Haartyhanks Customer Order Grid
 *
 * @category   Haartyhanks
 * @package    Haartyhanks_CustomerOrderGrid
 * @author     Haartyhanks Dev Team <support@haartyhanks.com>
 * @copyright  Copyright (c) Haartyhanks
 * @link       https://haartyhanks.com
 * @since      1.0.0
 */


namespace Haartyhanks\CustomerOrderGrid\Block\Adminhtml\Edit\Tab;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Customer\Controller\RegistryConstants;

class Orders extends \Magento\Customer\Block\Adminhtml\Edit\Tab\Orders
{
    protected $orderFactory;

    /**
     * Orders constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Helper\Reorder $salesReorder
     * @param \Magento\Framework\Registry $coreRegistry
     * @param OrderFactory $orderFactory Order factory dependency for loading order data
     * @param array $data Additional data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory,
        \Magento\Sales\Helper\Reorder $salesReorder,
        \Magento\Framework\Registry $coreRegistry,
        OrderFactory $orderFactory,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $backendHelper, $collectionFactory, $salesReorder, $coreRegistry, $data);
    }

    
    /**
     * Prepare columns for the customer order grid.
     *
     * Adds custom columns such as Product Name and Order Status using custom renderers.
     * Removes the default store_id column.
     * 
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();


        $this->removeColumn('store_id');

        $this->addColumnAfter(
            'custom_product_names',
            [
                'header' => __('Product Name'),
                'index' => 'entity_id',
                'renderer' => \Haartyhanks\CustomerOrderGrid\Block\Adminhtml\Edit\Tab\Column\ProductNames::class,
                'filter' => false,
                 'sortable' => false,
            ],
            'shipping_name'
        );
        $this->addColumn('billing_name', ['header' => __('Bill-to Name'), 'index' => 'billing_name']);
        $this->addColumn('shipping_name', ['header' => __('Ship-to Name'), 'index' => 'shipping_name']);


        $this->addColumnAfter(
            'custom_status',
            [
                'header' => __('Order Status'),
                'index' => 'entity_id',
                'renderer' => \Haartyhanks\CustomerOrderGrid\Block\Adminhtml\Edit\Tab\Column\OrderStatus::class,
                'filter' => false,
                'sortable' => false,
            ],
            'shipping_name'
        );

        $this->addColumn(
            'grand_total',
            [
                'header' => __('Order Total'),
                'index' => 'grand_total',
                'type' => 'currency',
                'currency' => 'order_currency_code',
                'rate'  => 1
            ]
        );
       

        if ($this->_salesReorder->isAllow()) {
            $this->addColumn(
                'action',
                [
                    'header' => 'Action',
                    'filter' => false,
                    'sortable' => false,
                    'width' => '100px',
                    'renderer' => \Magento\Sales\Block\Adminhtml\Reorder\Renderer\Action::class
                ]
            );
        }

        return $this;
    }
}
