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


declare(strict_types=1);

namespace Mirasvit\Helpdesk\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirasvit\Helpdesk\Model\ResourceModel\CustomerNote as CustomerNoteResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Message as MessageResource;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket as TicketResource;

/**
 * Detach a deleted customer from their support history, and drop the note held about them.
 *
 * Tickets and messages survive: they are the record of a conversation, and the module already keeps
 * the identical record when the requester has no account at all. The agent's private note about the
 * customer does not survive - there is nothing left for it to be about.
 */
class CustomerDeleteObserver implements ObserverInterface
{
    private $ticketResource;

    private $messageResource;

    private $customerNoteResource;

    public function __construct(
        TicketResource       $ticketResource,
        MessageResource      $messageResource,
        CustomerNoteResource $customerNoteResource
    ) {
        $this->ticketResource       = $ticketResource;
        $this->messageResource      = $messageResource;
        $this->customerNoteResource = $customerNoteResource;
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer') ?: $observer->getData('data_object');

        if (!$customer) {
            return;
        }

        $customerId = (int)$customer->getId();

        $this->ticketResource->detachCustomer($customerId);
        $this->messageResource->detachCustomer($customerId);
        $this->customerNoteResource->deleteByCustomerId($customerId);
    }
}
