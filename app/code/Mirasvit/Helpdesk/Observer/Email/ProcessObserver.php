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



namespace Mirasvit\Helpdesk\Observer\Email;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProcessObserver implements ObserverInterface
{
    /**
     * @var \Mirasvit\Helpdesk\Helper\History
     */
    private $helpdeskHistory;
    /**
     * @var \Mirasvit\Helpdesk\Model\TicketFactory
     */
    private $ticketFactory;

    /**
     * ProcessObserver constructor.
     * @param \Mirasvit\Helpdesk\Helper\History $helpdeskHistory
     * @param \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory
     */
    public function __construct(
        \Mirasvit\Helpdesk\Helper\History $helpdeskHistory,
        \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory
    ) {
        $this->helpdeskHistory = $helpdeskHistory;
        $this->ticketFactory   = $ticketFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $ticket      = $observer->getData('ticket');
        $user        = $observer->getData('user');
        $customer    = $observer->getData('customer');
        $triggeredBy = $observer->getData('triggeredBy');
        $email       = $observer->getData('email');
        $data = [
            'user'     => $user,
            'customer' => $customer,
            'email'    => $email,
        ];

        $stateBefore = $this->ticketFactory->create()->addData((array)$ticket->getOrigData());
        $this->helpdeskHistory->changeTicket(
            $ticket,
            $stateBefore,
            $ticket,
            $triggeredBy,
            $data
        );
    }
}