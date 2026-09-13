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

class Getopen extends \Mirasvit\Helpdesk\Controller\Ticket
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        if (!$request->isAjax()) {
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirect->setUrl('/customer/account');
            return $redirect;
        }

        $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson = $this->resultJsonFactory->create();

        $customer = $this->_getSession()->getCustomer();
        $amount = $this->helpdeskTicket->getOpenTicketsAmount($customer->getId());
        if ($amount) {
            $amount = $this->helpdeskTicket->formatTicketLabel($amount);
        }
        $response = $resultJson->setData(['amount' => $amount]);
        return $response;
    }
}
