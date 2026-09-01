<?php
declare(strict_types=1);

namespace Nilesh\Reorder\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Nilesh\Reorder\Helper\Mail;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;
    protected $helperMail;

    /**
     * ChangeColor constructor.
     * @param Action\Context $context
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Action\Context $context,
        OrderCollectionFactory $orderCollectionFactory,
        Mail $helperMail
    ) {
        $this->orderCollectionFactory   = $orderCollectionFactory;
        $this->helperMail               = $helperMail;
        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->getRequest();

        $orderIds = $request->getPost('selected', []);
        if (empty($orderIds)) {
            $this->getMessageManager()->addErrorMessage(__('No orders found.'));
            return $this->_redirect('sales/order/index');
        }

        // print_r($orderIds); exit(); // Selected Order Ids

        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('entity_id', ['in' => $orderIds]);

        try {
            //our logic
            $countDeleteOrder = 0;
            foreach ($orderCollection as $order) {
                if (!$order->getEntityId()) {
                    continue;
                }
                $email = $order->getCustomerEmail();
                // print_r($email); exit();
                $tempVar = array(
                    'order_id' => $order->getEntityId(),
                    'customer_name' => $order->getCustomerName()
                );
                $this->helperMail->sendReorderTemplateEmail($email, $tempVar);
                // exit();
                $countDeleteOrder++;
            }
            // \print_r($orderCollection->count()); exit();
            $countNonDeleteOrder = $orderCollection->count() - $countDeleteOrder;

            if ($countNonDeleteOrder && $countDeleteOrder) {
                $this->messageManager->addError(__('%1  reorder email(s) have not been sent successfully.', $countNonDeleteOrder));
            } elseif ($countNonDeleteOrder) {
                $this->messageManager->addError(__('%1  reorder email(s) have not been sent successfully.', $countNonDeleteOrder));
            }

            if ($countDeleteOrder) {
                $this->messageManager->addSuccess(__('%1 reorder email(s) have been sent successfully.', $countDeleteOrder));
            }

        } catch (\Exception $e) {
            $message = "An unknown error occurred while changing selected orders.";
            $this->getMessageManager()->addErrorMessage($e);
        }

        return $this->_redirect('sales/order/index');
    }
}