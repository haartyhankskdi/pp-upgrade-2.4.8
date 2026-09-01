<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Observer\Layout;

use Magento\Framework\Event\ObserverInterface;

class Load implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $fullActionName = $observer->getEvent()->getFullActionName();

        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $observer->getEvent()->getLayout();
        $handler = '';
        if ($fullActionName == 'catalog_product_view') {
            $handler = 'catalog_product_view_sagepay';
        }

        if ($handler) {
            $layout->getUpdate()->addHandle($handler);
        }
    }
}
