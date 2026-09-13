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


namespace Mirasvit\Helpdesk\Service\Order;

class OrderManagement implements \Mirasvit\Helpdesk\Api\Service\Order\OrderManagementInterface
{
    /**
     * @var \Mirasvit\Helpdesk\Api\Repository\TicketRepositoryInterface
     */
    private $ticketRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * OrderManagement constructor.
     * @param \Mirasvit\Helpdesk\Api\Repository\TicketRepositoryInterface $ticketRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Mirasvit\Helpdesk\Api\Repository\TicketRepositoryInterface $ticketRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->ticketRepository      = $ticketRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getTicketsAmount($order)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $order->getId())
        ;

        return $this->ticketRepository->getList($searchCriteria->create())->getTotalCount();
    }
}

