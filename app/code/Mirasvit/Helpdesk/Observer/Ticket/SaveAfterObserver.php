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



namespace Mirasvit\Helpdesk\Observer\Ticket;

use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Mirasvit\Helpdesk\Model\Config;

class SaveAfterObserver implements ObserverInterface
{
    /**
     * @var \Mirasvit\Helpdesk\Helper\History
     */
    private $helpdeskHistory;
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;
    /**
     * @var \Mirasvit\Helpdesk\Model\TicketFactory
     */
    private $ticketFactory;

    /**
     * SaveAfterObserver constructor.
     * @param \Mirasvit\Helpdesk\Helper\History $helpdeskHistory
     * @param \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Mirasvit\Helpdesk\Helper\History $helpdeskHistory,
        \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory,
        \Magento\Framework\App\State $state
    ) {
        $this->helpdeskHistory = $helpdeskHistory;
        $this->ticketFactory   = $ticketFactory;
        $this->state           = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Mirasvit\Helpdesk\Model\Ticket $ticket */
        $ticket = $observer->getData('object');

        try {
            $areaCode = $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            $areaCode = '';
        }

        if (!$areaCode || !in_array($areaCode, [Area::AREA_ADMINHTML, Area::AREA_FRONTEND]) ||
            $ticket->getSkipHistory() || $ticket->getIsMigration()
        ) {
            return;
        }

        $user = null;
        $customer = null;
        switch ($areaCode) {
            case Area::AREA_ADMINHTML:
                $triggeredBy = Config::USER;
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                /** @var \Magento\Backend\Model\Auth\Session $session */
                $session = $objectManager->get('Magento\Backend\Model\Auth\Session');
                $user = $session->getUser();
                break;
            case Area::AREA_FRONTEND:
                $triggeredBy = Config::CUSTOMER;
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                /** @var \Magento\Customer\Model\Session $session */
                $session = $objectManager->get('Magento\Customer\Model\Session');
                $customer = $session->getCustomer();
                break;
        }
        $data = [
            'user'     => $user,
            'customer' => $customer,
        ];
        if ($ticket->getRule()) {
            $triggeredBy = Config::RULE;
            $data['rule'] = $ticket->getRule();
        }

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