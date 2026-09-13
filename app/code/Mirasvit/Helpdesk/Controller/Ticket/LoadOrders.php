<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Controller\Ticket;

use Magento\Framework\Controller\ResultFactory;

class LoadOrders extends \Mirasvit\Helpdesk\Controller\Ticket
{
    /**
     * Do search of customer's orders.
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultPage */
        $resultPage    = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $customerId    = $this->_getSession()->getCustomerId();
        $customerEmail = $this->_getSession()->getCustomer()->getEmail();
        $storeId       = $this->_getSession()->getCustomer()->getStoreId();

        $orders = $this->helpdeskOrder->getOrderArray($customerEmail, $customerId, $storeId);

        $result = [];
        foreach ($orders as $value) {
            $result[] = [
                'name' => $this->helpdeskOrder->getOrderLabel($value['id']),
                'id'   => $value['id'],
            ];
        }
        $resultPage->setData($result);

        return $resultPage;
    }
}
