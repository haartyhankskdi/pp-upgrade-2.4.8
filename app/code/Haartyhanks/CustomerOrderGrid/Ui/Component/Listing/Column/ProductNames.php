<?php
namespace Haartyhanks\CustomerOrderGrid\Ui\Component\Listing\Column;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ProductNames extends Column
{
    protected $orderRepository;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $orderId = $item['entity_id'];
                try {
                    $order = $this->orderRepository->get($orderId);
                    $productNames = [];
                    foreach ($order->getAllVisibleItems() as $product) {
                        $productNames[] = $product->getName();
                    }
                    $item['product_names'] = implode(', ', $productNames);
                    $item['order_status'] = $order->getStatusLabel();
                } catch (\Exception $e) {
                    $item['product_names'] = __('N/A');
                    $item['order_status'] = __('N/A');
                }
            }
        }

        return $dataSource;
    }
}
