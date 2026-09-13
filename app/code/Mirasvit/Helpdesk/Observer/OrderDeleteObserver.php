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
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket as TicketResource;

/**
 * Unlink a deleted order from the tickets that referenced it.
 *
 * The ticket stays. A support conversation is about more than the order it was filed against.
 */
class OrderDeleteObserver implements ObserverInterface
{
    private $ticketResource;

    public function __construct(TicketResource $ticketResource)
    {
        $this->ticketResource = $ticketResource;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getData('order') ?: $observer->getData('data_object');

        if (!$order) {
            return;
        }

        $this->ticketResource->detachOrder((int)$order->getId());
    }
}
