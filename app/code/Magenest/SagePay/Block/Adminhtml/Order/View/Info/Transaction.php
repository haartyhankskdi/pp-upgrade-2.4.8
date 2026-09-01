<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\Order\View\Info;

use Magento\Backend\Block\Template\Context;
use Magenest\SagePay\Model\ResourceModel\Transaction\CollectionFactory;
use Magento\Framework\Registry;

class Transaction extends \Magento\Backend\Block\Template
{
    protected $_transFactory;

    protected $_registry;

    public function __construct(
        Context $context,
        CollectionFactory $transactionFactory,
        Registry $registry,
        array $data = []
    ) {
        $this->_transFactory = $transactionFactory;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    public function getTransaction()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_registry->registry('current_order');
        $orderId = $order->getId();

        $transaction = $this->_transFactory->create()
            ->addFieldToFilter('order_id', $orderId)
            ->setPageSize(1)
            ->getFirstItem();

        if ($transaction) {
            return $transaction;
        }

        return false;
    }
}
