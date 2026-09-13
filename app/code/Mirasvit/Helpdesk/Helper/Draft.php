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



namespace Mirasvit\Helpdesk\Helper;

class Draft extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Mirasvit\Helpdesk\Model\DraftFactory
     */
    protected $draftFactory;

    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Draft\CollectionFactory
     */
    protected $draftCollectionFactory;

    /**
     * @var \Magento\User\Model\ResourceModel\User\CollectionFactory
     */
    protected $userCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Mirasvit\Helpdesk\Helper\StringUtil
     */
    protected $helpdeskString;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $context;

    /**
     * @param \Mirasvit\Helpdesk\Model\DraftFactory                          $draftFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Draft\CollectionFactory $draftCollectionFactory
     * @param \Magento\User\Model\ResourceModel\User\CollectionFactory       $userCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                    $date
     * @param \Mirasvit\Helpdesk\Helper\StringUtil                               $helpdeskString
     * @param \Magento\Framework\App\Helper\Context                          $context
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\DraftFactory $draftFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Draft\CollectionFactory $draftCollectionFactory,
        \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Mirasvit\Helpdesk\Helper\StringUtil $helpdeskString,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->draftFactory = $draftFactory;
        $this->draftCollectionFactory = $draftCollectionFactory;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->date = $date;
        $this->helpdeskString = $helpdeskString;
        $this->context = $context;
        parent::__construct($context);
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return void
     */
    public function clearDraft($ticket)
    {
        $ticketId = $ticket->getId();
        $collection = $this->draftCollectionFactory->create();
        $collection->addFieldToFilter('ticket_id', $ticketId);
        foreach ($collection as $item) {
            $item->delete();
        }

        return;
    }

    /**
     * @param int $ticketId
     *
     * @return bool|\Mirasvit\Helpdesk\Model\Draft
     */
    public function getSavedDraft($ticketId)
    {
        $collection = $this->draftCollectionFactory->create()
                ->addFieldToFilter('ticket_id', $ticketId);
        if ($collection->count()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Register the current editor and drop the ones that went offline.
     *
     * Presence drives the "another agent is editing this ticket" lock, so only a real agent registers
     * itself: an integration id is not an admin user id and must never be written here.
     *
     * @param array    $usersOnline
     * @param int|null $userId
     *
     * @return array
     */
    private function refreshUsersOnline(array $usersOnline, $userId)
    {
        $timeNow = $this->date->gmtTimestamp();

        if ($userId) {
            $usersOnline[$userId] = $timeNow ? : [];
        }

        foreach ($usersOnline as $uId => $timestamp) {
            if (($userId && $uId == $userId) || $timestamp + 20 >= $timeNow) {
                continue;
            }
            unset($usersOnline[$uId]); //other user went offline from this page
        }

        return $usersOnline;
    }

    /**
     * @param int         $ticketId
     * @param int|null    $userId Admin user editing the draft, or null for an integration (API) client
     * @param bool|string $text
     *
     * @return \Mirasvit\Helpdesk\Model\Draft
     */
    public function getCurrentDraft($ticketId, $userId, $text = false)
    {
        $collection = $this->draftCollectionFactory->create()
                ->addFieldToFilter('ticket_id', $ticketId);
        if ($collection->count()) {
            $draft = $collection->getFirstItem();
        } else {
            $draft = $this->draftFactory->create();
            $draft->setTicketId($ticketId);
        }
        $draft->setUsersOnline($this->refreshUsersOnline((array) $draft->getUsersOnline(), $userId));
        if ($text !== false) {
            // Normalize line endings to LF. The whole draft pipeline (autosave, render template) assumes
            // \n as the only newline convention, so CRLF/CR supplied by API clients (e.g. the AI agent)
            // leaves stray carriage returns that break the inline draft script and render as \r\n\r\n.
            $text = str_replace(["\r\n", "\r"], "\n", (string) $text);
            $draft->setBody($text);
            $draft->setUpdatedBy($userId ? : null);
            $draft->setUpdatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }
        $draft->save();

        return $draft;
    }

    /**
     * @param int         $ticketId
     * @param int         $userId
     * @param bool|string $text
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getNoticeMessage($ticketId, $userId, $text = false)
    {
        $draft = $this->getCurrentDraft($ticketId, $userId, $text);
        $ids = $draft->getUsersOnline();
        unset($ids[$userId]);
        $ids = array_keys($ids);
        if (!count($ids)) {
            return '';
        }
        $users = $this->userCollectionFactory->create()
                    ->addFieldToFilter('main_table.user_id', $ids);
        $userNames = [];
        $editNotice = '';
        foreach ($users as $user) {
            $draftUser = $draft->getUser();
            if (!$draftUser) {
                continue;
            }
            if ($userId != $draftUser->getId()) {
                $editNotice = __('%1 is editing now', $draftUser->getName());
                continue;
            }
            $userNames[] = $user->getName();
        }
        if (count($userNames) == 0) {
            return $editNotice;
        }
        if (count($userNames) == 1) {
            return __('%1 has opened this ticket %2', implode(', ', $userNames), '<br>'.$editNotice);
        } else {
            return __('%1 have opened this ticket %2', implode(', ', $userNames), '<br>'.$editNotice);
        }
    }
}
