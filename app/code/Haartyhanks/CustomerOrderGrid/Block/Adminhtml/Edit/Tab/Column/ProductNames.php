<?php

/**
 * Haartyhanks Customer Order Grid - Product Names Renderer
 *
 * @category   Haartyhanks
 * @package    Haartyhanks_CustomerOrderGrid
 * @author     Haartyhanks Dev Team <support@haartyhanks.com>
 * @copyright  Copyright (c) Haartyhanks
 * @link       https://haartyhanks.com
 * @since      1.0.0
 */


namespace Haartyhanks\CustomerOrderGrid\Block\Adminhtml\Edit\Tab\Column;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class ProductNames extends AbstractRenderer
{
    protected $orderRepository;

    
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    /**
     * ProductNames renderer for displaying product names in the order grid.
     * Retrieves and displays a comma-separated list of product names for a given order row.
     */
    public function render(DataObject $row)
    {
        $orderId = $row->getData('entity_id');
        try {
            $order = $this->orderRepository->get($orderId);
            $names = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $names[] = $item->getName();
            }
            return implode(', ', $names);
        } catch (\Exception $e) {
            return __('N/A');
        }
    }
}
