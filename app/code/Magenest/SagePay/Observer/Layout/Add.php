<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Observer\Layout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class Add implements ObserverInterface
{
    protected $_logger;

    public function __construct(
        LoggerInterface $loggerInterface
    ) {
        $this->_logger = $loggerInterface;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        if ($product->getTypeId() != 'grouped') {
            $product->setHasOptions(true);
        }
    }
}
