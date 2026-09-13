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



namespace Mirasvit\Helpdesk\Observer;

use \Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Magento\Framework\Event\ObserverInterface;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory;
use Mirasvit\Helpdesk\Model\UserFactory;
use Magento\Framework\App\Request\Http;

class SaveUserSignatureObserver implements ObserverInterface
{
    private $ticketCollectionFactory;

    private $userFactory;

    private $request;

    public function __construct(
        CollectionFactory $ticketCollectionFactory,
        UserFactory $userFactory,
        Http $request
    ) {
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->userFactory             = $userFactory;
        $this->request                 = $request;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\User\Model\User $user */
        $user = $observer->getObject();
        if (!$user->getId() || $this->request->getParam('signature') === null) {
            return;
        }

        $helpdeskUser = $this->userFactory->create()->load($user->getId());

        $helpdeskUser->setSignature($this->request->getParam('signature'));

        $helpdeskUser->setId($user->getId());

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
