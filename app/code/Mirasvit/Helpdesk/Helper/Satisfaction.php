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



namespace Mirasvit\Helpdesk\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Satisfaction\CollectionFactory as SatisfactionCollectionFactory;
use Mirasvit\Helpdesk\Model\SatisfactionFactory;
use Mirasvit\Helpdesk\Model\TicketFactory;

class Satisfaction extends DataObject
{
    protected $ticketFactory;

    protected $satisfactionFactory;

    protected $messageCollectionFactory;

    protected $satisfactionCollectionFactory;

    protected $helpdeskNotification;

    protected $context;

    public function __construct(
        TicketFactory $ticketFactory,
        SatisfactionFactory $satisfactionFactory,
        CollectionFactory $messageCollectionFactory,
        SatisfactionCollectionFactory $satisfactionCollectionFactory,
        Notification $helpdeskNotification,
        Context $context
    ) {
        $this->ticketFactory                 = $ticketFactory;
        $this->satisfactionFactory           = $satisfactionFactory;
        $this->messageCollectionFactory      = $messageCollectionFactory;
        $this->satisfactionCollectionFactory = $satisfactionCollectionFactory;
        $this->helpdeskNotification          = $helpdeskNotification;
        $this->context                       = $context;

        parent::__construct();
    }

    /**
     * @param string $messageUid
     * @param int    $rate
     *
     * @return \Mirasvit\Helpdesk\Model\Satisfaction|false
     *
     * @throws \Exception
     */
    public function addRate($messageUid, $rate)
    {
        try {
            $message = $this->getMessageByUid($messageUid);
        } catch (LocalizedException $e) {
            return false;
        }

        $satisfaction = $this->getSatisfactionByMessage($message);

        $ticket = $this->ticketFactory->create()->load($message->getTicketId());

        $satisfaction->setTicketId($message->getTicketId())
            ->setMessageId($message->getId())
            ->setCustomerId($message->getCustomerId())
            ->setUserId($message->getUserId())
            ->setStoreId($ticket->getStoreId())
            ->setRate($rate)
            ->save();

        $this->helpdeskNotification->sendNotificationStaffNewSatisfaction($satisfaction);

        return $satisfaction;
    }

    /**
     * @param string $messageUid
     * @param string $comment
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addComment($messageUid, $comment)
    {
        $message = $this->getMessageByUid($messageUid);
        $satisfaction = $this->getSatisfactionByMessage($message);
        $satisfaction->setComment($comment)
            ->save();

        $this->helpdeskNotification->sendNotificationStaffNewSatisfaction($satisfaction);
    }

    /**
     * @param string $messageUid
     *
     * @return \Mirasvit\Helpdesk\Model\Message
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMessageByUid($messageUid)
    {
        $messages = $this->messageCollectionFactory->create()
                    ->addFieldToFilter('uid', $messageUid);

        if (!$messages->count()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Wrong URL'));
        }

        $message = $messages->getFirstItem();

        return $message;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Message $message
     *
     * @return \Mirasvit\Helpdesk\Model\Satisfaction
     */
    public function getSatisfactionByMessage($message)
    {
        $satisfactions = $this->satisfactionCollectionFactory->create()
            ->addFieldToFilter('message_id', $message->getId());

        if ($satisfactions->count()) {
            $satisfaction = $satisfactions->getFirstItem();
        } else {
            $satisfaction = $this->satisfactionFactory->create();
        }

        $satisfaction->setTicketId($message->getTicketId());

        return $satisfaction;
    }
}
