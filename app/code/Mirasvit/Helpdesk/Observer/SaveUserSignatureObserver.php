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


namespace Mirasvit\Helpdesk\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Mirasvit\Helpdesk\Api\Data\TicketInterface;

class SaveUserSignatureObserver implements ObserverInterface
{
    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory
     */
    private $ticketCollectionFactory;
    /**
     * @var \Mirasvit\Helpdesk\Model\UserFactory
     */
    private $userFactory;

    /**
     * SaveUserSignatureObserver constructor.
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory
     * @param \Mirasvit\Helpdesk\Model\UserFactory $userFactory
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory,
        \Mirasvit\Helpdesk\Model\UserFactory $userFactory
    ) {
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->userFactory = $userFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\User\Model\User $user */
        $user = $observer->getObject();
        if (!$user->getId()) {
            return;
        }
        $helpdeskUser = $this->userFactory->create();
        $helpdeskUser->setId($user->getId());
        $helpdeskUser->setSignature($user->getSignature());
        $helpdeskUser->getResource()->save($helpdeskUser);

        if ($user->hasData('is_active') && !$user->getIsActive()) {
            $collection = $this->ticketCollectionFactory->create();
            $connection = $collection->getConnection();

            $ids = $collection->addFieldToFilter(TicketInterface::KEY_USER_ID, $user->getId())->getAllIds();
            if ($ids) {
                $connection->update(
                    $collection->getTable('mst_helpdesk_ticket'),
                    [TicketInterface::KEY_USER_ID => 0],
                    $collection->getIdFieldName() . ' in (' . implode(',', $ids) . ')'
                );
            }
            $connection->delete(
                $collection->getTable('mst_helpdesk_department_user'),
                'du_user_id = ' . $user->getId()
            );
        }
    }
}
