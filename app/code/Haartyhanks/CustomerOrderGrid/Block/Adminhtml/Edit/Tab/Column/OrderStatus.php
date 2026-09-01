<?php
namespace Haartyhanks\CustomerOrderGrid\Block\Adminhtml\Edit\Tab\Column;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class OrderStatus extends AbstractRenderer
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

    public function render(DataObject $row)
    {
       // $status = $row->getStatus(); // Get order status value
       

        $orderId = $row->getData('entity_id');
        try {
            $order = $this->orderRepository->get($orderId);
            $status = $order->getStatus();

            

             switch ($status) {
                 case 'pending':
                     $color = '#FFA500'; // orange
                     break;
                 case 'processing':
                     $color = '#007bff'; // blue
                     break;
                 case 'complete':
                     $color = '#28a745'; // green
                     break;
                 case 'canceled':
                     $color = '#dc3545'; // red
                     break;
                 case 'closed':
                     $color = '#6c757d'; // grey
                     break;
                 case 'approve':
                     $color = '#17a2b8'; // teal blue
                     break; 
                 default:
                     $color = '#343a40'; // dark
                     break;
             }
             $buttonHtml = '<button style="background-color: ' . $color . '; color: white; border: none; padding: 5px 10px; border-radius: 3px;">' . ucfirst($status) . '</button>';
            return $buttonHtml;
        } catch (\Exception $e) {   
            return __('N/A');
        }
    }
}
