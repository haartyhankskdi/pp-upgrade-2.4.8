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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Controller\Adminhtml\Ticket;

use Magento\Framework\Controller\ResultFactory;

class LoadOrders extends \Mirasvit\Helpdesk\Controller\Adminhtml\Ticket
{
    /**
     * Do search of customers.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultPage */
        $resultPage    = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $customerId    = $this->getRequest()->getParam('customer_id', '');
        $customerEmail = $this->getRequest()->getParam('email', '');

        $orders = $this->helpdeskOrder->getOrderArray($customerEmail, $customerId);

        $result = [
            [
                'name' => (string)__('Unassigned'),
                'id'   => 0,
            ],
        ];
        foreach ($orders as $value) {
            $result[] = [
                'name' => $value['name'],
                'id'   => $value['id'],
                'url'  => $value['url'],
            ];
        }
        $resultPage->setData($result);

        return $resultPage;
    }
}
