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



namespace Mirasvit\Helpdesk\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Mirasvit\Helpdesk\Model\ResourceModel\Priority\CollectionFactory as PriorityCollectionFactory;
use Mirasvit\Helpdesk\Api\Service\Ticket\TicketManagementInterface;
use Mirasvit\Helpdesk\Model\CustomerNoteFactory;
use Mirasvit\Helpdesk\Model\DepartmentFactory;
use Mirasvit\Helpdesk\Model\PriorityFactory;
use Mirasvit\Helpdesk\Model\StatusFactory;
use Magento\User\Model\UserFactory as MagentoUserFactory;
use Magento\Store\Model\StoreFactory;
use Mirasvit\Helpdesk\Model\MessageFactory;
use Magento\Customer\Model\CustomerFactory;
use Mirasvit\Helpdesk\Model\EmailFactory;
use Magento\Sales\Model\OrderFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory as DepartmentCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;
use Mirasvit\Helpdesk\Model\Config;
use Mirasvit\Helpdesk\Helper\Notification;
use Mirasvit\Helpdesk\Helper\History as HelpdeskHistory;
use Mirasvit\Helpdesk\Helper\StringUtil;
use Mirasvit\Helpdesk\Helper\Ruleevent;
use Mirasvit\Helpdesk\Helper\Email as HelpdeskEmail;
use Mirasvit\Helpdesk\Helper\Attachment as HelpdeskAttachment;
use Magento\Framework\Url as UrlManager;
use Magento\Backend\Model\Url as BackendUrlManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\ResourceConnection;

/**
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Collection|\Mirasvit\Helpdesk\Model\Ticket[] getCollection()
 * @method $this load(int $id)
 * @method bool getIsMassDelete()
 * @method $this setIsMassDelete(bool $flag)
 * @method bool getIsMassStatus()
 * @method $this setIsMassStatus(bool $flag)
 * @method bool getAllowSendInternal()
 * @method $this setAllowSendInternal(bool $flag)
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Ticket getResource()
 * @method int[] getTagIds()
 * @method $this setTagIds(array $ids)
 * @method string getEmailSubjectPrefix()
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Ticket extends \Mirasvit\Helpdesk\Model\TicketBridge implements IdentityInterface
{
    const CACHE_TAG = 'helpdesk_ticket';

    public $isNew;

    protected $_cacheTag = 'helpdesk_ticket';

    protected $_eventPrefix = 'helpdesk_ticket';

    private $tagCollectionFactory;

    private $helpdeskEmail;

    private $config;

    private $orderFactory;

    private $customerFactory;

    private $emailFactory;

    private $statusFactory;

    private $localeDate;

    private $messageCollectionFactory;

    private $backendUrlManager;

    private $urlManager;

    private $ticketManagement;

    private $helpdeskNotification;

    private $helpdeskRuleevent;

    private $helpdeskString;

    private $helpdeskHistory;

    private $helpdeskAttachment;

    private $departmentCollectionFactory;

    private $messageFactory;

    private $storeFactory;

    private $userFactory;

    private $priorityFactory;

    private $priorityCollectionFactory;

    private $departmentFactory;

    private $storeManager;

    private $customerNoteFactory;

    private $resourceConnection;

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function __construct(
        PriorityCollectionFactory $priorityCollectionFactory,
        TicketManagementInterface $ticketManagement,
        CustomerNoteFactory $customerNoteFactory,
        DepartmentFactory $departmentFactory,
        PriorityFactory $priorityFactory,
        StatusFactory $statusFactory,
        MagentoUserFactory $userFactory,
        StoreFactory $storeFactory,
        MessageFactory $messageFactory,
        CustomerFactory $customerFactory,
        EmailFactory $emailFactory,
        OrderFactory $orderFactory,
        DepartmentCollectionFactory $departmentCollectionFactory,
        MessageCollectionFactory $messageCollectionFactory,
        TagCollectionFactory $tagCollectionFactory,
        Config $config,
        Notification $helpdeskNotification,
        HelpdeskHistory $helpdeskHistory,
        StringUtil $helpdeskString,
        Ruleevent $helpdeskRuleevent,
        HelpdeskEmail $helpdeskEmail,
        HelpdeskAttachment $helpdeskAttachment,
        UrlManager $urlManager,
        BackendUrlManager $backendUrlManager,
        TimezoneInterface $localeDate,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        Context $context,
        Registry $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->priorityCollectionFactory   = $priorityCollectionFactory;
        $this->ticketManagement            = $ticketManagement;
        $this->customerNoteFactory         = $customerNoteFactory;
        $this->departmentFactory           = $departmentFactory;
        $this->priorityFactory             = $priorityFactory;
        $this->statusFactory               = $statusFactory;
        $this->userFactory                 = $userFactory;
        $this->storeFactory                = $storeFactory;
        $this->messageFactory              = $messageFactory;
        $this->customerFactory             = $customerFactory;
        $this->emailFactory                = $emailFactory;
        $this->orderFactory                = $orderFactory;
        $this->departmentCollectionFactory = $departmentCollectionFactory;
        $this->messageCollectionFactory    = $messageCollectionFactory;
        $this->tagCollectionFactory        = $tagCollectionFactory;
        $this->config                      = $config;
        $this->helpdeskNotification        = $helpdeskNotification;
        $this->helpdeskHistory             = $helpdeskHistory;
        $this->helpdeskString              = $helpdeskString;
        $this->helpdeskRuleevent           = $helpdeskRuleevent;
        $this->helpdeskEmail               = $helpdeskEmail;
        $this->helpdeskAttachment          = $helpdeskAttachment;
        $this->urlManager                  = $urlManager;
        $this->backendUrlManager           = $backendUrlManager;
        $this->localeDate                  = $localeDate;
        $this->storeManager                = $storeManager;
        $this->resourceConnection          = $resourceConnection;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\ResourceModel\Ticket');
    }

    /**
     * @param bool|false $emptyOption
     * @return array
     */
    public function toOptionArray($emptyOption = false)
    {
        return $this->getCollection()->toOptionArray($emptyOption);
    }

    /**
     * @var Department|null
     */
    protected $department = null;

    /**
     * @return Department|false
     */
    public function getDepartment()
    {
        if (!$this->getDepartmentId()) {
            return false;
        }
        if ($this->department === null) {
            $this->department = $this->departmentFactory->create()->load($this->getDepartmentId());
        }

        return $this->department;
    }

    /**
     * @var Priority
     */
    protected $priority = null;

    /**
     * @return Priority|false
     */
    public function getPriority()
    {
        if (!$this->getPriorityId()) {
            return false;
        }

        if ($this->priority === null) {
            $this->priority = $this->priorityFactory->create()->load($this->getPriorityId());
        }

        return $this->priority;
    }

    /**
     * @var Status
     */
    protected $status = null;

    /**
     * @return Status|false
     */
    public function getStatus()
    {
        if (!$this->getStatusId()) {
            return false;
        }
        if ($this->status === null) {
            $this->status = $this->statusFactory->create()->load($this->getStatusId());
        }

        return $this->status;
    }

    /**
     * @var \Magento\User\Model\User
     */
    protected $user = null;

    /**
     * @return \Magento\User\Model\User|false
     */
    public function getUser()
    {
        if (!$this->getUserId()) {
            return false;
        }
        if ($this->user === null) {
            $this->user = $this->userFactory->create()->load($this->getUserId());
        }

        return $this->user;
    }

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $store = null;

    /**
     * @return \Magento\Store\Model\Store|false
     */
    public function getStore()
    {
        if ($this->getStoreId() === null) {
            return false;
        }
        if ($this->store === null) {
            $this->store = $this->storeFactory->create()->load($this->getStoreId());
        }

        return $this->store;
    }

    /**
     * @return string[]
     */
    public function getCc()
    {
        $cc = $this->getData('cc');
        if ($cc) {
            $cc = explode(',', $cc);
            $cc = array_map('trim', $cc);

            return $cc;
        }

        return [];
    }

    /**
     * @return string[]
     */
    public function getBcc()
    {
        $cc = $this->getData('bcc');
        if ($cc) {
            $cc = explode(',', $cc);
            $cc = array_map('trim', $cc);

            return $cc;
        }

        return [];
    }

    /************************/

    /**
     * @param string                                                               $text
     * @param \Magento\Customer\Model\Customer|\Magento\Framework\DataObject|false $customer
     * @param \Magento\User\Model\User|false                                       $user
     * @param string                                                               $triggeredBy
     * @param string                                                               $messageType
     * @param \Mirasvit\Helpdesk\Model\Email|false                                 $email
     * @param int|false                                                            $bodyFormat
     *
     * @return \Mirasvit\Helpdesk\Model\Message
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) 
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addMessage(
        $text,
        $customer,
        $user,
        $triggeredBy,
        $messageType = Config::MESSAGE_PUBLIC,
        $email = false,
        $bodyFormat = false
    ) {
        $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,10}(\/\S*)?[_?]{1}]/";
        $text = preg_replace_callback(
            $reg_exUrl,
            function ($matches) {
                while (!preg_match('/[a-zA-Z0-9=#]/', substr($matches[0], -1)))
                {
                    $matches[0] = substr($matches[0], 0, -1);
                }

                return $matches[0] . ' ';
            },
            $text
        );
        $text = $this->htmlemoji($text);
        $message = $this->messageFactory->create()
            ->setTicketId($this->getId())
            ->setType($messageType)
            ->setBody($text)
            ->setBodyFormat($bodyFormat)
            ->setTriggeredBy($triggeredBy);



        //We should not add reply to the locked tickets from frontend
        if ($this->getOrigData('status_id')
            && in_array($this->getOrigData()['status_id'], $this->config->getGeneralLockedStatusList())) {
            return $message;
        }

        if ($triggeredBy == Config::CUSTOMER) {
            /** @var \Magento\Customer\Model\Customer|\Magento\Framework\DataObject $customer */
            $message->setCustomerId($customer->getId());
            $message->setCustomerName($customer->getName());
            $message->setCustomerEmail($customer->getEmail());
            $message->setIsRead(true);

            $this->setLastReplyName($customer->getName());
        } elseif ($triggeredBy == Config::USER) {
            /** @var \Magento\User\Model\User $user */
            $message->setUserId($user->getId());
            if ($this->getOrigData('department_id') == $this->getData('department_id') &&
                $this->getOrigData('user_id') == $this->getData('user_id')
            ) {
                if ($messageType != Config::MESSAGE_INTERNAL) {
                    $this->setUserId($user->getId());
                    // In case of different departments of ticket and owner, correct department id
                    $departments = $this->departmentCollectionFactory->create();
                    $departments->addUserFilter($user->getId())
                        ->addFieldToFilter('is_active', true);
                    if ($departments->count() && !in_array($this->getDepartmentId(), $departments->getAllIds())) {
                        $this->department = null;
                        $this->setDepartmentId($departments->getFirstItem()->getId());
                    }
                }
            }
            $this->setLastReplyName($user->getName());
            if ($message->isThirdParty()) {
                $message->setThirdPartyEmail($this->getThirdPartyEmail());
            }
        } elseif ($triggeredBy == Config::THIRD) {
            $message->setThirdPartyEmail(($email ? $email->getFromEmail() : '') ? : $this->getThirdPartyEmail());
            if ($email) {
                $this->setLastReplyName($email->getSenderNameOrEmail());
                $message->setThirdPartyName($email->getSenderName());
            }
        }
        if ($email) {
            $message->setEmailId($email->getId());
        }

        //if ticket was closed, then we have new message from customer, we will open it
        if ($triggeredBy != Config::USER) {
            if ($this->isClosed()) {
                $status = $this->statusFactory->create()->loadByCode(Config::STATUS_OPEN);
                $this->setStatusId($status->getId());
            }
            if ($this->getFolder() !== Config::FOLDER_SPAM) {
                $this->setFolder(Config::FOLDER_INBOX);
            }
        }

        $message->save();

        if ($email) {
            $email->setIsProcessed(true)
                ->setAttachmentMessageId($message->getId())
                ->save();
        } else {
            $this->helpdeskAttachment->saveAttachments($message);
        }

        if ($this->canNotify($email)) {
            if ($this->getReplyCnt() == 0) {
                $this->helpdeskNotification->newTicket($this, $customer, $user, $triggeredBy, $messageType);
            } else {
                $this->helpdeskNotification->newMessage($this, $customer, $user, $triggeredBy, $messageType);
            }
        }

        $this->setReplyCnt($this->getReplyCnt() + 1);
        if (!$this->getFirstReplyAt() && $user) {
            $this->setFirstReplyAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }
        if ($messageType != Config::MESSAGE_INTERNAL) {
            $this->setLastReplyAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }

        $this->addToSearchIndex($text);
        $this->setSkipHistory(true);
        $this->save();
        $this->helpdeskHistory->addMessage(
            $this,
            $triggeredBy,
            ['customer' => $customer, 'user' => $user, 'email' => $email],
            $messageType
        );

        return $message;
    }

    /**
     * Whether adding this message may send outgoing mail.
     *
     * Notifying is also what fires the workflow rules for the event, because
     * Notification::newTicket()/newMessage() call Ruleevent::newEvent() - so this single
     * predicate governs both.
     *
     * Two kinds of message must stay silent:
     *  - spam, which has always been excluded here;
     *  - a fetched auto-reply, per RFC 3834: never respond to a message that is itself
     *    automatic. Otherwise a rule emails the responder, the responder auto-replies, that
     *    is fetched, and the rule fires again - a loop bounded only by the remote's own rate
     *    limiting (Gmail sends one per 4 days) or its willingness to honour the suppression
     *    headers we set in Notification::getMstCustomHeaders().
     *
     * $email is absent for messages typed by a customer or an agent, which always notify.
     *
     * @param \Mirasvit\Helpdesk\Model\Email|false $email
     * @return bool
     */
    private function canNotify($email): bool
    {
        if ($this->getFolder() === Config::FOLDER_SPAM) {
            return false;
        }

        return !($email && $email->getIsAutosubmitted());
    }

    private function htmlemoji($str)
    {
        $emoji_pattern = "/\\x{1F469}|\\x{200D}|\\x{1F7E1}|\\x{2764}|\\x{FE0F}|\\x{200D}|\\x{1F48B}|\\x{200D}|\\x{1F469}|\\x{1F469}\\x{200D}\\x{2764}\\x{FE0F}\\x{200D}\\x{1F48B}\\x{200D}\\x{1F468}|\\x{1F468}\\x{200D}\\x{2764}\\x{FE0F}\\x{200D}\\x{1F48B}\\x{200D}\\x{1F468}|\\x{1F469}\\x{200D}\\x{1F469}\\x{200D}\\x{1F466}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F468}\\x{200D}\\x{1F467}\\x{200D}\\x{1F466}|\\x{1F469}\\x{200D}\\x{1F469}\\x{200D}\\x{1F467}\\x{200D}\\x{1F467}|\\x{1F469}\\x{200D}\\x{1F469}\\x{200D}\\x{1F467}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F469}\\x{200D}\\x{1F467}\\x{200D}\\x{1F467}|\\x{1F468}\\x{200D}\\x{1F469}\\x{200D}\\x{1F467}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F469}\\x{200D}\\x{1F466}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F468}\\x{200D}\\x{1F467}\\x{200D}\\x{1F467}|\\x{1F468}\\x{200D}\\x{1F468}\\x{200D}\\x{1F466}\\x{200D}\\x{1F466}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{2764}\\x{FE0F}\\x{200D}\\x{1F469}|\\x{1F469}\\x{200D}\\x{2764}\\x{FE0F}\\x{200D}\\x{1F468}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{2764}\\x{FE0F}\\x{200D}\\x{1F468}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F469}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F469}\\x{200D}\\x{1F467}|\\x{1F468}\\x{200D}\\x{1F467}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F468}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F468}\\x{200D}\\x{1F467}|\\x{1F469}\\x{200D}\\x{1F469}\\x{200D}\\x{1F467}|\\x{1F469}\\x{200D}\\x{1F469}\\x{200D}\\x{1F466}|\\x{1F469}\\x{200D}\\x{1F467}\\x{200D}\\x{1F467}|\\x{1F469}\\x{200D}\\x{1F467}\\x{200D}\\x{1F466}|\\x{1F469}\\x{200D}\\x{1F466}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F467}\\x{200D}\\x{1F467}|\\x{1F468}\\x{200D}\\x{1F466}\\x{200D}\\x{1F466}|\\x{1F645}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F645}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F646}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F646}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FB}|\\x{1F646}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F646}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F646}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F645}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F646}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F646}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F646}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F646}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F647}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F646}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F645}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F645}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F93E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F487}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F487}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F487}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F487}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F487}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F487}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F487}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F487}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F487}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F93E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F93E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F645}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F93E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F93D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F93D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F93D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F93D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F93D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F645}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F645}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F647}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F645}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F645}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F647}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F3CA}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F647}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F64D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F3C4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F3C4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F64D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F3C4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F64D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F3C4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F64D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F64D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F64D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F3C4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F3C4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F64D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F3C4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F64D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F64D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F3C3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F64D}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F3C3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FE}|\\x{1F64E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F64E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F64E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F64E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F64E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F64B}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F64B}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F647}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F3CA}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F647}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F647}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F647}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F3CA}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F647}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F3CA}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F647}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F3CA}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F3CA}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F64B}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F64B}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F64B}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F3C4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F3CA}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F64B}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F3CA}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F64B}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F3CA}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F3CA}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F64B}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F3C4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F64B}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F3C4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F64B}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F487}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F486}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F486}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F46E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FD}|\\x{1F93E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FC}|\\x{1F46E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F46E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F46E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F46E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F46E}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F93D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F46E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FF}|\\x{1F46E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F46E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F46E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F93E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F471}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F471}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F471}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F471}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F471}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F471}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F93E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FE}|\\x{1F471}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FF}|\\x{1F93E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FC}|\\x{1F93E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{2696}\\x{FE0F}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{2695}\\x{FE0F}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FC}|\\x{1F471}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F471}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F486}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F482}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F481}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F481}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F481}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F481}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F482}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F482}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F482}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F482}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F482}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F441}\\x{FE0F}\\x{200D}\\x{1F5E8}\\x{FE0F}|\\x{1F482}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F481}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F482}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F482}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F482}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F486}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F486}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F486}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F486}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F3C3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F486}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F486}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F486}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F481}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F481}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F471}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F473}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F93E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F473}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F473}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F473}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F473}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F473}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F473}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F473}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F473}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F473}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F477}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F481}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F477}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F477}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F477}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F477}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F477}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F477}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F477}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F477}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F477}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F481}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F481}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F3C3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F3C3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{2708}\\x{FE0F}\\x{1F3FF}|\\x{1F6B5}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F938}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F6B6}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F938}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F6B5}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F938}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F6B5}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F6B5}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F6B5}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F6B5}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F938}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F6B5}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F6B5}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F6B6}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F6B5}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F6B5}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F938}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F6B4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F6B4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F6B4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F937}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F6B4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F6B4}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F6B4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F6B4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F6B4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F6B6}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F938}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F938}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F926}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F937}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F926}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F926}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F937}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F926}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F926}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F937}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F926}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F926}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F937}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F926}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F926}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F937}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F6B6}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F926}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F937}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F937}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F6B6}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F6B6}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F937}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F6B6}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F64E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F6B6}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F6B6}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F938}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F6B6}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F938}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F938}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F6B4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F6A3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F6A3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F939}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FB}|\\x{1F939}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F939}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F939}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F939}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F6A3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F93D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F6A3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F6A3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F93D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F93D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F939}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F6A3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F93D}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F3C3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F3C3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F64E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F3C3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F64E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F64E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F3C3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FE}|\\x{1F64E}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F3C3}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F939}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FF}|\\x{1F937}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F939}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FD}|\\x{1F6A3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FE}|\\x{1F6B4}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F6A3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FD}|\\x{1F6A3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FC}|\\x{1F939}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FB}|\\x{1F939}\\x{200D}\\x{2640}\\x{FE0F}\\x{1F3FC}|\\x{1F6A3}\\x{200D}\\x{2642}\\x{FE0F}\\x{1F3FF}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F3CB}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F3CC}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F575}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2640}\\x{FE0F}|\\x{26F9}\\x{FE0F}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F468}\\x{200D}\\x{1F4BC}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F33E}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F3EB}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F4BC}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F33E}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F3EB}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F527}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F3EB}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F4BC}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F527}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F3A8}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F3A8}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F3EB}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F527}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F3EB}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F3A8}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F527}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F3A8}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F3A8}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F527}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F3A4}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F3A4}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F33E}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F393}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F3ED}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F3A4}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F3ED}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F393}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F3ED}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F393}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F3ED}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F393}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F4BB}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F393}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F4BB}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F3ED}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F33E}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F4BB}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F373}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F3A4}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F373}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F4BB}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F373}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F4BB}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F4BC}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F373}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F4BC}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F3A4}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F33E}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F373}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F33E}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F52C}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F4BB}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F4BC}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F4BC}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F4BC}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F4BC}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F4BB}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F4BB}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F4BB}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F4BB}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F527}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F3ED}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F3ED}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F3ED}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F3ED}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F3ED}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F3EB}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F52C}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F4BC}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F527}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F3EB}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F680}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F692}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F692}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F692}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F692}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F692}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F680}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F680}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F680}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F527}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F680}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F52C}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F52C}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F52C}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F52C}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F52C}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F527}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F527}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F3EB}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F3EB}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F3EB}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F692}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F3A8}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F33E}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F33E}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F33E}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F692}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F692}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F692}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F692}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F373}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F680}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F680}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F680}\\x{1F3FD}|\\x{1F468}\\x{200D}\\x{1F680}\\x{1F3FC}|\\x{1F468}\\x{200D}\\x{1F680}\\x{1F3FB}|\\x{1F468}\\x{200D}\\x{1F52C}\\x{1F3FF}|\\x{1F468}\\x{200D}\\x{1F52C}\\x{1F3FE}|\\x{1F468}\\x{200D}\\x{1F52C}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F373}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F33E}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F373}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F393}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F3A8}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F3A4}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F3A4}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F3A4}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F3A4}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F3A8}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F3A4}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F393}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F3A8}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F393}\\x{1F3FD}|\\x{1F469}\\x{200D}\\x{1F393}\\x{1F3FC}|\\x{1F469}\\x{200D}\\x{1F393}\\x{1F3FB}|\\x{1F469}\\x{200D}\\x{1F373}\\x{1F3FF}|\\x{1F469}\\x{200D}\\x{1F373}\\x{1F3FE}|\\x{1F469}\\x{200D}\\x{1F3A8}\\x{1F3FC}|\\x{1F3F3}\\x{FE0F}\\x{200D}\\x{1F308}|\\x{1F3C4}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F468}\\x{200D}\\x{2695}\\x{FE0F}|\\x{1F3C3}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F3C4}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F468}\\x{200D}\\x{2708}\\x{FE0F}|\\x{1F3CA}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F3CA}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F468}\\x{200D}\\x{2696}\\x{FE0F}|\\x{1F3C3}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F93D}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F6B4}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F93C}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F487}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F937}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F6B5}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F93E}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F6B4}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F645}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F6B6}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F645}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F938}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F646}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F6A3}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F926}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F93D}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F93C}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F6B5}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F939}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F646}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F647}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F647}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F64B}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F939}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F64B}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F938}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F64D}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F6B6}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F64D}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F6A3}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F64E}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F487}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F64E}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F486}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F477}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F471}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F46F}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F471}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F473}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F926}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F46F}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F46E}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F46E}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F473}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F477}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F93E}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F469}\\x{200D}\\x{2708}\\x{FE0F}|\\x{1F481}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F486}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F482}\\x{200D}\\x{2642}\\x{FE0F}|\\x{1F469}\\x{200D}\\x{2696}\\x{FE0F}|\\x{1F937}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F481}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F482}\\x{200D}\\x{2640}\\x{FE0F}|\\x{1F469}\\x{200D}\\x{2695}\\x{FE0F}|\\x{1F468}\\x{200D}\\x{1F680}|\\x{1F469}\\x{200D}\\x{1F52C}|\\x{1F468}\\x{200D}\\x{1F3A8}|\\x{1F468}\\x{200D}\\x{1F373}|\\x{1F469}\\x{200D}\\x{1F692}|\\x{1F468}\\x{200D}\\x{1F466}|\\x{1F468}\\x{200D}\\x{1F4BB}|\\x{1F468}\\x{200D}\\x{1F393}|\\x{1F469}\\x{200D}\\x{1F3EB}|\\x{1F469}\\x{200D}\\x{1F373}|\\x{1F468}\\x{200D}\\x{1F3ED}|\\x{1F468}\\x{200D}\\x{1F4BC}|\\x{1F469}\\x{200D}\\x{1F680}|\\x{1F468}\\x{200D}\\x{1F3A4}|\\x{1F468}\\x{200D}\\x{1F467}|\\x{1F468}\\x{200D}\\x{1F33E}|\\x{1F469}\\x{200D}\\x{1F527}|\\x{1F468}\\x{200D}\\x{1F692}|\\x{1F469}\\x{200D}\\x{1F393}|\\x{1F468}\\x{200D}\\x{1F52C}|\\x{1F469}\\x{200D}\\x{1F3A4}|\\x{1F468}\\x{200D}\\x{1F3EB}|\\x{1F469}\\x{200D}\\x{1F4BB}|\\x{1F469}\\x{200D}\\x{1F467}|\\x{1F469}\\x{200D}\\x{1F4BC}|\\x{1F469}\\x{200D}\\x{1F466}|\\x{1F469}\\x{200D}\\x{1F3A8}|\\x{1F468}\\x{200D}\\x{1F527}|\\x{1F469}\\x{200D}\\x{1F3ED}|\\x{1F469}\\x{200D}\\x{1F33E}|\\x{0039}\\x{FE0F}\\x{20E3}|\\x{0030}\\x{FE0F}\\x{20E3}|\\x{0037}\\x{FE0F}\\x{20E3}|\\x{0036}\\x{FE0F}\\x{20E3}|\\x{0023}\\x{FE0F}\\x{20E3}|\\x{002A}\\x{FE0F}\\x{20E3}|\\x{0038}\\x{FE0F}\\x{20E3}|\\x{0034}\\x{FE0F}\\x{20E3}|\\x{0031}\\x{FE0F}\\x{20E3}|\\x{0033}\\x{FE0F}\\x{20E3}|\\x{0035}\\x{FE0F}\\x{20E3}|\\x{0032}\\x{FE0F}\\x{20E3}|\\x{1F1F0}\\x{1F1FF}|\\x{1F1EE}\\x{1F1F9}|\\x{1F1F0}\\x{1F1F3}|\\x{1F1F1}\\x{1F1F0}|\\x{1F1F0}\\x{1F1F7}|\\x{1F1F0}\\x{1F1FC}|\\x{1F1EF}\\x{1F1EA}|\\x{1F930}\\x{1F3FE}|\\x{1F1F1}\\x{1F1EE}|\\x{1F1EF}\\x{1F1F2}|\\x{1F1F1}\\x{1F1F8}|\\x{1F1EF}\\x{1F1F4}|\\x{1F1F1}\\x{1F1F7}|\\x{1F933}\\x{1F3FB}|\\x{1F1EF}\\x{1F1F5}|\\x{1F1F1}\\x{1F1E6}|\\x{1F1F0}\\x{1F1F2}|\\x{1F1F0}\\x{1F1EA}|\\x{1F1F0}\\x{1F1EC}|\\x{1F933}\\x{1F3FC}|\\x{1F1F0}\\x{1F1FE}|\\x{1F1F1}\\x{1F1E8}|\\x{1F930}\\x{1F3FF}|\\x{1F1F0}\\x{1F1ED}|\\x{1F1F0}\\x{1F1EE}|\\x{1F1F0}\\x{1F1F5}|\\x{1F1F1}\\x{1F1E7}|\\x{1F918}\\x{1F3FF}|\\x{1F1EE}\\x{1F1F8}|\\x{1F1EB}\\x{1F1F7}|\\x{1F1EC}\\x{1F1F1}|\\x{1F1EC}\\x{1F1EE}|\\x{1F1EC}\\x{1F1ED}|\\x{1F1EC}\\x{1F1EC}|\\x{1F6C0}\\x{1F3FC}|\\x{1F1EC}\\x{1F1EB}|\\x{1F1EC}\\x{1F1EA}|\\x{1F1EC}\\x{1F1E9}|\\x{1F1EC}\\x{1F1E7}|\\x{1F1EC}\\x{1F1E6}|\\x{1F6C0}\\x{1F3FB}|\\x{1F1EB}\\x{1F1F4}|\\x{1F1EC}\\x{1F1F3}|\\x{1F1EB}\\x{1F1F2}|\\x{1F1EB}\\x{1F1F0}|\\x{1F1EB}\\x{1F1EF}|\\x{1F933}\\x{1F3FF}|\\x{1F934}\\x{1F3FB}|\\x{1F934}\\x{1F3FC}|\\x{1F934}\\x{1F3FD}|\\x{1F934}\\x{1F3FE}|\\x{1F934}\\x{1F3FF}|\\x{1F935}\\x{1F3FB}|\\x{1F935}\\x{1F3FC}|\\x{1F1EC}\\x{1F1F2}|\\x{1F1EC}\\x{1F1F5}|\\x{1F1EE}\\x{1F1F7}|\\x{1F1ED}\\x{1F1F3}|\\x{1F1EE}\\x{1F1F6}|\\x{1F1EE}\\x{1F1F4}|\\x{1F1EE}\\x{1F1F3}|\\x{1F1EE}\\x{1F1F2}|\\x{1F1EE}\\x{1F1F1}|\\x{1F1EE}\\x{1F1EA}|\\x{1F1EE}\\x{1F1E9}|\\x{1F1EE}\\x{1F1E8}|\\x{1F1ED}\\x{1F1FA}|\\x{1F1ED}\\x{1F1F9}|\\x{1F1ED}\\x{1F1F7}|\\x{1F1ED}\\x{1F1F2}|\\x{1F1EC}\\x{1F1F6}|\\x{1F933}\\x{1F3FD}|\\x{1F1ED}\\x{1F1F0}|\\x{1F1EC}\\x{1F1FE}|\\x{1F1EC}\\x{1F1FA}|\\x{1F1EC}\\x{1F1F9}|\\x{1F933}\\x{1F3FE}|\\x{1F6C0}\\x{1F3FF}|\\x{1F6C0}\\x{1F3FE}|\\x{1F6C0}\\x{1F3FD}|\\x{1F1EC}\\x{1F1F8}|\\x{1F1EC}\\x{1F1F7}|\\x{1F1EC}\\x{1F1FC}|\\x{1F1F2}\\x{1F1F5}|\\x{1F1F1}\\x{1F1F9}|\\x{1F1F5}\\x{1F1EC}|\\x{1F1F5}\\x{1F1FE}|\\x{1F1F5}\\x{1F1FC}|\\x{1F1F5}\\x{1F1F9}|\\x{1F1F5}\\x{1F1F8}|\\x{1F1F5}\\x{1F1F7}|\\x{1F1F5}\\x{1F1F3}|\\x{1F1F5}\\x{1F1F2}|\\x{1F1F5}\\x{1F1F1}|\\x{1F1F5}\\x{1F1F0}|\\x{1F1F5}\\x{1F1ED}|\\x{1F1F5}\\x{1F1EB}|\\x{1F1F7}\\x{1F1EA}|\\x{1F918}\\x{1F3FC}|\\x{1F918}\\x{1F3FB}|\\x{1F91C}\\x{1F3FB}|\\x{1F91C}\\x{1F3FC}|\\x{1F91C}\\x{1F3FD}|\\x{1F91C}\\x{1F3FE}|\\x{1F91C}\\x{1F3FF}|\\x{1F91E}\\x{1F3FB}|\\x{1F91E}\\x{1F3FC}|\\x{1F91E}\\x{1F3FD}|\\x{1F1F6}\\x{1F1E6}|\\x{1F1F7}\\x{1F1F4}|\\x{1F91E}\\x{1F3FF}|\\x{1F1F8}\\x{1F1F1}|\\x{1F1F8}\\x{1F1FF}|\\x{1F1F8}\\x{1F1FE}|\\x{1F1F8}\\x{1F1FD}|\\x{1F1F8}\\x{1F1FB}|\\x{1F1F8}\\x{1F1F9}|\\x{1F1F8}\\x{1F1F8}|\\x{1F1F8}\\x{1F1F7}|\\x{1F1F8}\\x{1F1F4}|\\x{1F1F8}\\x{1F1F3}|\\x{1F1F8}\\x{1F1F2}|\\x{1F1F8}\\x{1F1F0}|\\x{1F1F7}\\x{1F1F8}|\\x{1F1F8}\\x{1F1EF}|\\x{1F1F8}\\x{1F1EE}|\\x{1F1F8}\\x{1F1ED}|\\x{1F1F8}\\x{1F1EC}|\\x{1F1F8}\\x{1F1EA}|\\x{1F1F8}\\x{1F1E9}|\\x{1F1F8}\\x{1F1E8}|\\x{1F1F8}\\x{1F1E7}|\\x{1F1F8}\\x{1F1E6}|\\x{1F1F7}\\x{1F1FC}|\\x{1F1F7}\\x{1F1FA}|\\x{1F91E}\\x{1F3FE}|\\x{1F926}\\x{1F3FB}|\\x{1F1F1}\\x{1F1FA}|\\x{1F1F2}\\x{1F1ED}|\\x{1F1F2}\\x{1F1F6}|\\x{1F1F2}\\x{1F1F4}|\\x{1F1F2}\\x{1F1F3}|\\x{1F1F2}\\x{1F1F2}|\\x{1F1F2}\\x{1F1F1}|\\x{1F930}\\x{1F3FB}|\\x{1F930}\\x{1F3FC}|\\x{1F930}\\x{1F3FD}|\\x{1F6CC}\\x{1F3FF}|\\x{1F1F2}\\x{1F1F0}|\\x{1F1F2}\\x{1F1EC}|\\x{1F1F2}\\x{1F1F8}|\\x{1F1F2}\\x{1F1EB}|\\x{1F1F2}\\x{1F1EA}|\\x{1F6CC}\\x{1F3FE}|\\x{1F6CC}\\x{1F3FD}|\\x{1F6CC}\\x{1F3FC}|\\x{1F6CC}\\x{1F3FB}|\\x{1F1F2}\\x{1F1E9}|\\x{1F1F2}\\x{1F1E8}|\\x{1F1F2}\\x{1F1E6}|\\x{1F1F1}\\x{1F1FE}|\\x{1F1F1}\\x{1F1FB}|\\x{1F1F2}\\x{1F1F7}|\\x{1F1F2}\\x{1F1F9}|\\x{1F1F5}\\x{1F1EA}|\\x{1F926}\\x{1F3FD}|\\x{1F1F5}\\x{1F1E6}|\\x{1F1F4}\\x{1F1F2}|\\x{1F1F3}\\x{1F1FF}|\\x{1F1F3}\\x{1F1FA}|\\x{1F926}\\x{1F3FC}|\\x{1F1F3}\\x{1F1F7}|\\x{1F1F3}\\x{1F1F5}|\\x{1F1F3}\\x{1F1F4}|\\x{1F1F3}\\x{1F1F1}|\\x{1F1F3}\\x{1F1EE}|\\x{1F1F3}\\x{1F1EC}|\\x{1F1F2}\\x{1F1FA}|\\x{1F1F3}\\x{1F1EB}|\\x{1F1F3}\\x{1F1EA}|\\x{1F1F3}\\x{1F1E8}|\\x{1F1F3}\\x{1F1E6}|\\x{1F926}\\x{1F3FE}|\\x{1F1F2}\\x{1F1FF}|\\x{1F1F2}\\x{1F1FE}|\\x{1F1F2}\\x{1F1FD}|\\x{1F1F2}\\x{1F1FC}|\\x{1F1F2}\\x{1F1FB}|\\x{1F926}\\x{1F3FF}|\\x{1F6B6}\\x{1F3FF}|\\x{1F1E9}\\x{1F1EF}|\\x{1F6B6}\\x{1F3FE}|\\x{1F647}\\x{1F3FD}|\\x{1F64C}\\x{1F3FB}|\\x{1F64B}\\x{1F3FF}|\\x{1F1E8}\\x{1F1E8}|\\x{1F64B}\\x{1F3FE}|\\x{1F64B}\\x{1F3FD}|\\x{1F1E8}\\x{1F1E6}|\\x{1F64B}\\x{1F3FC}|\\x{1F64B}\\x{1F3FB}|\\x{1F647}\\x{1F3FF}|\\x{1F647}\\x{1F3FE}|\\x{1F647}\\x{1F3FC}|\\x{1F64C}\\x{1F3FD}|\\x{1F647}\\x{1F3FB}|\\x{1F646}\\x{1F3FF}|\\x{1F646}\\x{1F3FE}|\\x{1F646}\\x{1F3FD}|\\x{1F646}\\x{1F3FC}|\\x{1F646}\\x{1F3FB}|\\x{1F645}\\x{1F3FF}|\\x{1F645}\\x{1F3FE}|\\x{1F645}\\x{1F3FD}|\\x{1F645}\\x{1F3FC}|\\x{1F64C}\\x{1F3FC}|\\x{1F64C}\\x{1F3FE}|\\x{1F91B}\\x{1F3FB}|\\x{1F64F}\\x{1F3FD}|\\x{1F93D}\\x{1F3FD}|\\x{1F93D}\\x{1F3FE}|\\x{1F93D}\\x{1F3FF}|\\x{1F93E}\\x{1F3FB}|\\x{1F93E}\\x{1F3FC}|\\x{1F93E}\\x{1F3FD}|\\x{1F93E}\\x{1F3FE}|\\x{1F93E}\\x{1F3FF}|\\x{1F64F}\\x{1F3FF}|\\x{1F64F}\\x{1F3FE}|\\x{1F64F}\\x{1F3FC}|\\x{1F64C}\\x{1F3FF}|\\x{1F64F}\\x{1F3FB}|\\x{1F64E}\\x{1F3FF}|\\x{1F64E}\\x{1F3FE}|\\x{1F64E}\\x{1F3FD}|\\x{1F64E}\\x{1F3FC}|\\x{1F64E}\\x{1F3FB}|\\x{1F64D}\\x{1F3FF}|\\x{1F64D}\\x{1F3FE}|\\x{1F64D}\\x{1F3FD}|\\x{1F64D}\\x{1F3FC}|\\x{1F64D}\\x{1F3FB}|\\x{1F645}\\x{1F3FB}|\\x{1F91B}\\x{1F3FE}|\\x{1F93D}\\x{1F3FB}|\\x{1F1E6}\\x{1F1EC}|\\x{1F1E6}\\x{1F1FA}|\\x{1F1E6}\\x{1F1F9}|\\x{1F1E6}\\x{1F1F8}|\\x{1F91A}\\x{1F3FB}|\\x{1F1E6}\\x{1F1F7}|\\x{1F1E6}\\x{1F1F6}|\\x{1F1E6}\\x{1F1F4}|\\x{1F1E6}\\x{1F1F2}|\\x{1F1E6}\\x{1F1F1}|\\x{1F1E6}\\x{1F1EE}|\\x{1F1E6}\\x{1F1EB}|\\x{1F1E6}\\x{1F1FD}|\\x{1F1E6}\\x{1F1EA}|\\x{1F1E6}\\x{1F1E9}|\\x{1F91B}\\x{1F3FF}|\\x{1F919}\\x{1F3FF}|\\x{1F919}\\x{1F3FE}|\\x{1F919}\\x{1F3FD}|\\x{1F919}\\x{1F3FC}|\\x{1F919}\\x{1F3FB}|\\x{1F918}\\x{1F3FD}|\\x{1F1E6}\\x{1F1E8}|\\x{1F918}\\x{1F3FE}|\\x{1F1E6}\\x{1F1FC}|\\x{1F91A}\\x{1F3FC}|\\x{1F91A}\\x{1F3FF}|\\x{1F1E7}\\x{1F1F2}|\\x{1F1E7}\\x{1F1FF}|\\x{1F1E7}\\x{1F1FE}|\\x{1F1E7}\\x{1F1FC}|\\x{1F1E7}\\x{1F1FB}|\\x{1F1E7}\\x{1F1F9}|\\x{1F1E7}\\x{1F1F8}|\\x{1F1E7}\\x{1F1F7}|\\x{1F1E7}\\x{1F1F6}|\\x{1F1E7}\\x{1F1F4}|\\x{1F1E7}\\x{1F1F3}|\\x{1F1E7}\\x{1F1F1}|\\x{1F1E6}\\x{1F1FF}|\\x{1F91A}\\x{1F3FE}|\\x{1F1E7}\\x{1F1EF}|\\x{1F1E7}\\x{1F1EE}|\\x{1F1E7}\\x{1F1ED}|\\x{1F1E7}\\x{1F1EC}|\\x{1F1E7}\\x{1F1EB}|\\x{1F91A}\\x{1F3FD}|\\x{1F1E7}\\x{1F1EA}|\\x{1F1E7}\\x{1F1E9}|\\x{1F1E7}\\x{1F1E7}|\\x{1F1E7}\\x{1F1E6}|\\x{1F93D}\\x{1F3FC}|\\x{1F939}\\x{1F3FF}|\\x{1F6B6}\\x{1F3FD}|\\x{1F57A}\\x{1F3FD}|\\x{1F6B5}\\x{1F3FB}|\\x{1F575}\\x{1F3FE}|\\x{1F575}\\x{1F3FF}|\\x{1F1E9}\\x{1F1F2}|\\x{1F1E9}\\x{1F1F0}|\\x{1F1F9}\\x{1F1E8}|\\x{1F1E9}\\x{1F1EC}|\\x{1F1E9}\\x{1F1EA}|\\x{1F57A}\\x{1F3FB}|\\x{1F57A}\\x{1F3FC}|\\x{1F57A}\\x{1F3FE}|\\x{1F575}\\x{1F3FC}|\\x{1F57A}\\x{1F3FF}|\\x{1F1E8}\\x{1F1FF}|\\x{1F1E8}\\x{1F1FE}|\\x{1F1E8}\\x{1F1FD}|\\x{1F935}\\x{1F3FE}|\\x{1F1E8}\\x{1F1FC}|\\x{1F1E8}\\x{1F1FB}|\\x{1F1E8}\\x{1F1FA}|\\x{1F590}\\x{1F3FB}|\\x{1F590}\\x{1F3FC}|\\x{1F575}\\x{1F3FD}|\\x{1F575}\\x{1F3FB}|\\x{1F590}\\x{1F3FD}|\\x{1F1EA}\\x{1F1ED}|\\x{1F6B6}\\x{1F3FC}|\\x{1F6B6}\\x{1F3FB}|\\x{1F935}\\x{1F3FD}|\\x{1F6B5}\\x{1F3FF}|\\x{1F1EB}\\x{1F1EE}|\\x{1F1EA}\\x{1F1FA}|\\x{1F1EA}\\x{1F1F9}|\\x{1F1EA}\\x{1F1F8}|\\x{1F1EA}\\x{1F1F7}|\\x{1F6B5}\\x{1F3FE}|\\x{1F1EA}\\x{1F1EC}|\\x{1F1E9}\\x{1F1F4}|\\x{1F1EA}\\x{1F1EA}|\\x{1F1EA}\\x{1F1E8}|\\x{1F1EA}\\x{1F1E6}|\\x{1F6B5}\\x{1F3FD}|\\x{1F1E9}\\x{1F1FF}|\\x{1F574}\\x{1F3FB}|\\x{1F574}\\x{1F3FC}|\\x{1F574}\\x{1F3FD}|\\x{1F574}\\x{1F3FE}|\\x{1F6B5}\\x{1F3FC}|\\x{1F574}\\x{1F3FF}|\\x{1F6B4}\\x{1F3FF}|\\x{1F590}\\x{1F3FE}|\\x{1F939}\\x{1F3FE}|\\x{1F938}\\x{1F3FB}|\\x{1F936}\\x{1F3FB}|\\x{1F936}\\x{1F3FC}|\\x{1F936}\\x{1F3FD}|\\x{1F936}\\x{1F3FE}|\\x{1F936}\\x{1F3FF}|\\x{1F937}\\x{1F3FB}|\\x{1F937}\\x{1F3FC}|\\x{1F937}\\x{1F3FD}|\\x{1F937}\\x{1F3FE}|\\x{1F937}\\x{1F3FF}|\\x{1F938}\\x{1F3FC}|\\x{1F1E8}\\x{1F1E9}|\\x{1F938}\\x{1F3FD}|\\x{1F6A3}\\x{1F3FF}|\\x{1F6A3}\\x{1F3FE}|\\x{1F6A3}\\x{1F3FD}|\\x{1F6A3}\\x{1F3FC}|\\x{1F6A3}\\x{1F3FB}|\\x{1F938}\\x{1F3FE}|\\x{1F938}\\x{1F3FF}|\\x{1F939}\\x{1F3FB}|\\x{1F939}\\x{1F3FC}|\\x{1F939}\\x{1F3FD}|\\x{1F935}\\x{1F3FF}|\\x{1F1E8}\\x{1F1EB}|\\x{1F590}\\x{1F3FF}|\\x{1F596}\\x{1F3FC}|\\x{1F1E8}\\x{1F1F7}|\\x{1F595}\\x{1F3FB}|\\x{1F6B4}\\x{1F3FE}|\\x{1F595}\\x{1F3FC}|\\x{1F595}\\x{1F3FD}|\\x{1F595}\\x{1F3FE}|\\x{1F595}\\x{1F3FF}|\\x{1F1E8}\\x{1F1F5}|\\x{1F6B4}\\x{1F3FD}|\\x{1F596}\\x{1F3FB}|\\x{1F596}\\x{1F3FD}|\\x{1F1E8}\\x{1F1EC}|\\x{1F596}\\x{1F3FE}|\\x{1F596}\\x{1F3FF}|\\x{1F6B4}\\x{1F3FC}|\\x{1F1E8}\\x{1F1F4}|\\x{1F1E8}\\x{1F1F3}|\\x{1F1E8}\\x{1F1F2}|\\x{1F1E8}\\x{1F1F1}|\\x{1F1E8}\\x{1F1F0}|\\x{1F6B4}\\x{1F3FB}|\\x{1F1E8}\\x{1F1EE}|\\x{1F1E8}\\x{1F1ED}|\\x{1F1F9}\\x{1F1E6}|\\x{1F44A}\\x{1F3FF}|\\x{1F1F9}\\x{1F1E9}|\\x{1F44B}\\x{1F3FC}|\\x{1F44D}\\x{1F3FF}|\\x{1F44D}\\x{1F3FE}|\\x{1F44D}\\x{1F3FD}|\\x{1F44D}\\x{1F3FC}|\\x{1F44D}\\x{1F3FB}|\\x{1F44C}\\x{1F3FF}|\\x{1F44C}\\x{1F3FE}|\\x{1F44C}\\x{1F3FD}|\\x{1F44C}\\x{1F3FC}|\\x{1F44C}\\x{1F3FB}|\\x{1F44B}\\x{1F3FF}|\\x{1F44B}\\x{1F3FE}|\\x{1F44B}\\x{1F3FD}|\\x{1F44B}\\x{1F3FB}|\\x{1F44E}\\x{1F3FC}|\\x{1F91B}\\x{1F3FD}|\\x{1F44A}\\x{1F3FE}|\\x{1F44A}\\x{1F3FD}|\\x{1F44A}\\x{1F3FC}|\\x{1F44A}\\x{1F3FB}|\\x{1F449}\\x{1F3FF}|\\x{1F449}\\x{1F3FE}|\\x{1F449}\\x{1F3FD}|\\x{1F449}\\x{1F3FC}|\\x{1F449}\\x{1F3FB}|\\x{1F448}\\x{1F3FF}|\\x{1F448}\\x{1F3FE}|\\x{1F448}\\x{1F3FD}|\\x{1F44E}\\x{1F3FB}|\\x{1F44E}\\x{1F3FD}|\\x{1F448}\\x{1F3FB}|\\x{1F466}\\x{1F3FE}|\\x{1F469}\\x{1F3FC}|\\x{1F469}\\x{1F3FB}|\\x{1F468}\\x{1F3FF}|\\x{1F468}\\x{1F3FE}|\\x{1F468}\\x{1F3FD}|\\x{1F468}\\x{1F3FC}|\\x{1F468}\\x{1F3FB}|\\x{1F467}\\x{1F3FF}|\\x{1F467}\\x{1F3FE}|\\x{1F467}\\x{1F3FD}|\\x{1F467}\\x{1F3FC}|\\x{1F467}\\x{1F3FB}|\\x{1F466}\\x{1F3FF}|\\x{1F466}\\x{1F3FD}|\\x{1F44E}\\x{1F3FE}|\\x{1F466}\\x{1F3FC}|\\x{1F466}\\x{1F3FB}|\\x{1F450}\\x{1F3FF}|\\x{1F450}\\x{1F3FE}|\\x{1F450}\\x{1F3FD}|\\x{1F450}\\x{1F3FC}|\\x{1F450}\\x{1F3FB}|\\x{1F44F}\\x{1F3FF}|\\x{1F44F}\\x{1F3FE}|\\x{1F44F}\\x{1F3FD}|\\x{1F44F}\\x{1F3FC}|\\x{1F44F}\\x{1F3FB}|\\x{1F44E}\\x{1F3FF}|\\x{1F448}\\x{1F3FC}|\\x{1F447}\\x{1F3FF}|\\x{1F469}\\x{1F3FE}|\\x{1F3C3}\\x{1F3FE}|\\x{1F3CA}\\x{1F3FC}|\\x{1F3CA}\\x{1F3FB}|\\x{1F3C7}\\x{1F3FF}|\\x{1F3C7}\\x{1F3FE}|\\x{1F3C7}\\x{1F3FD}|\\x{1F3C7}\\x{1F3FC}|\\x{1F3C7}\\x{1F3FB}|\\x{1F3C4}\\x{1F3FF}|\\x{1F3C4}\\x{1F3FE}|\\x{1F3C4}\\x{1F3FD}|\\x{1F3C4}\\x{1F3FC}|\\x{1F3C4}\\x{1F3FB}|\\x{1F3C3}\\x{1F3FF}|\\x{1F3C3}\\x{1F3FD}|\\x{1F3CA}\\x{1F3FE}|\\x{1F3C3}\\x{1F3FC}|\\x{1F3C3}\\x{1F3FB}|\\x{1F3C2}\\x{1F3FF}|\\x{1F3C2}\\x{1F3FE}|\\x{1F3C2}\\x{1F3FD}|\\x{1F1F9}\\x{1F1EB}|\\x{1F3C2}\\x{1F3FC}|\\x{1F3C2}\\x{1F3FB}|\\x{1F385}\\x{1F3FF}|\\x{1F385}\\x{1F3FE}|\\x{1F385}\\x{1F3FD}|\\x{1F385}\\x{1F3FC}|\\x{1F385}\\x{1F3FB}|\\x{1F3CA}\\x{1F3FD}|\\x{1F3CA}\\x{1F3FF}|\\x{1F447}\\x{1F3FE}|\\x{1F442}\\x{1F3FF}|\\x{1F447}\\x{1F3FD}|\\x{1F447}\\x{1F3FC}|\\x{1F447}\\x{1F3FB}|\\x{1F446}\\x{1F3FF}|\\x{1F446}\\x{1F3FE}|\\x{1F446}\\x{1F3FD}|\\x{1F446}\\x{1F3FC}|\\x{1F446}\\x{1F3FB}|\\x{1F443}\\x{1F3FF}|\\x{1F443}\\x{1F3FE}|\\x{1F443}\\x{1F3FD}|\\x{1F443}\\x{1F3FC}|\\x{1F443}\\x{1F3FB}|\\x{1F442}\\x{1F3FE}|\\x{1F3CB}\\x{1F3FB}|\\x{1F442}\\x{1F3FD}|\\x{1F442}\\x{1F3FC}|\\x{1F442}\\x{1F3FB}|\\x{1F1F9}\\x{1F1ED}|\\x{1F3CC}\\x{1F3FF}|\\x{1F3CC}\\x{1F3FE}|\\x{1F3CC}\\x{1F3FD}|\\x{1F3CC}\\x{1F3FC}|\\x{1F3CC}\\x{1F3FB}|\\x{1F3CB}\\x{1F3FF}|\\x{1F3CB}\\x{1F3FE}|\\x{1F3CB}\\x{1F3FD}|\\x{1F3CB}\\x{1F3FC}|\\x{1F469}\\x{1F3FD}|\\x{1F91B}\\x{1F3FC}|\\x{1F469}\\x{1F3FF}|\\x{1F486}\\x{1F3FE}|\\x{1F1FC}\\x{1F1F8}|\\x{1F1FD}\\x{1F1F0}|\\x{1F1FE}\\x{1F1EA}|\\x{1F1FE}\\x{1F1F9}|\\x{1F1FF}\\x{1F1E6}|\\x{1F1FF}\\x{1F1F2}|\\x{1F1FF}\\x{1F1FC}|\\x{1F487}\\x{1F3FF}|\\x{1F487}\\x{1F3FE}|\\x{1F487}\\x{1F3FD}|\\x{1F487}\\x{1F3FC}|\\x{1F487}\\x{1F3FB}|\\x{1F486}\\x{1F3FF}|\\x{1F486}\\x{1F3FD}|\\x{1F1FB}\\x{1F1FA}|\\x{1F486}\\x{1F3FC}|\\x{1F486}\\x{1F3FB}|\\x{1F485}\\x{1F3FF}|\\x{1F485}\\x{1F3FE}|\\x{1F485}\\x{1F3FD}|\\x{1F485}\\x{1F3FC}|\\x{1F485}\\x{1F3FB}|\\x{1F483}\\x{1F3FF}|\\x{1F483}\\x{1F3FE}|\\x{1F483}\\x{1F3FD}|\\x{1F483}\\x{1F3FC}|\\x{1F483}\\x{1F3FB}|\\x{1F482}\\x{1F3FF}|\\x{1F1FC}\\x{1F1EB}|\\x{1F1FB}\\x{1F1F3}|\\x{1F482}\\x{1F3FD}|\\x{1F1FA}\\x{1F1EC}|\\x{1F46E}\\x{1F3FB}|\\x{1F1F9}\\x{1F1EC}|\\x{1F1F9}\\x{1F1F0}|\\x{1F1F9}\\x{1F1F1}|\\x{1F1F9}\\x{1F1F2}|\\x{1F1F9}\\x{1F1F3}|\\x{1F1F9}\\x{1F1F4}|\\x{1F1F9}\\x{1F1F7}|\\x{1F1F9}\\x{1F1F9}|\\x{1F1F9}\\x{1F1FB}|\\x{1F1F9}\\x{1F1FC}|\\x{1F1F9}\\x{1F1FF}|\\x{1F1FA}\\x{1F1E6}|\\x{1F1FA}\\x{1F1F2}|\\x{1F1FB}\\x{1F1EE}|\\x{1F1FA}\\x{1F1F3}|\\x{1F1FA}\\x{1F1F8}|\\x{1F1FA}\\x{1F1FE}|\\x{1F1FA}\\x{1F1FF}|\\x{1F1FB}\\x{1F1E6}|\\x{1F4AA}\\x{1F3FF}|\\x{1F4AA}\\x{1F3FE}|\\x{1F4AA}\\x{1F3FD}|\\x{1F4AA}\\x{1F3FC}|\\x{1F4AA}\\x{1F3FB}|\\x{1F1FB}\\x{1F1E8}|\\x{1F1FB}\\x{1F1EA}|\\x{1F1FB}\\x{1F1EC}|\\x{1F482}\\x{1F3FE}|\\x{1F1F9}\\x{1F1EF}|\\x{1F482}\\x{1F3FC}|\\x{1F472}\\x{1F3FC}|\\x{1F474}\\x{1F3FF}|\\x{1F474}\\x{1F3FE}|\\x{1F474}\\x{1F3FD}|\\x{1F474}\\x{1F3FC}|\\x{1F474}\\x{1F3FB}|\\x{1F473}\\x{1F3FF}|\\x{1F473}\\x{1F3FE}|\\x{1F473}\\x{1F3FD}|\\x{1F473}\\x{1F3FC}|\\x{1F482}\\x{1F3FB}|\\x{1F472}\\x{1F3FF}|\\x{1F472}\\x{1F3FE}|\\x{1F472}\\x{1F3FD}|\\x{1F472}\\x{1F3FB}|\\x{1F475}\\x{1F3FC}|\\x{1F471}\\x{1F3FF}|\\x{1F471}\\x{1F3FE}|\\x{1F471}\\x{1F3FD}|\\x{1F471}\\x{1F3FC}|\\x{1F471}\\x{1F3FB}|\\x{1F470}\\x{1F3FF}|\\x{1F470}\\x{1F3FE}|\\x{1F470}\\x{1F3FD}|\\x{1F470}\\x{1F3FC}|\\x{1F470}\\x{1F3FB}|\\x{1F46E}\\x{1F3FF}|\\x{1F46E}\\x{1F3FE}|\\x{1F46E}\\x{1F3FD}|\\x{1F46E}\\x{1F3FC}|\\x{1F475}\\x{1F3FB}|\\x{1F473}\\x{1F3FB}|\\x{1F475}\\x{1F3FD}|\\x{1F478}\\x{1F3FB}|\\x{1F481}\\x{1F3FF}|\\x{1F481}\\x{1F3FE}|\\x{1F481}\\x{1F3FD}|\\x{1F481}\\x{1F3FB}|\\x{1F47C}\\x{1F3FF}|\\x{1F47C}\\x{1F3FE}|\\x{1F47C}\\x{1F3FD}|\\x{1F47C}\\x{1F3FC}|\\x{1F47C}\\x{1F3FB}|\\x{1F478}\\x{1F3FF}|\\x{1F478}\\x{1F3FE}|\\x{1F478}\\x{1F3FD}|\\x{1F478}\\x{1F3FC}|\\x{1F481}\\x{1F3FC}|\\x{1F477}\\x{1F3FF}|\\x{1F477}\\x{1F3FD}|\\x{1F477}\\x{1F3FC}|\\x{1F477}\\x{1F3FB}|\\x{1F476}\\x{1F3FF}|\\x{1F476}\\x{1F3FE}|\\x{1F476}\\x{1F3FD}|\\x{1F476}\\x{1F3FC}|\\x{1F476}\\x{1F3FB}|\\x{1F477}\\x{1F3FE}|\\x{1F475}\\x{1F3FF}|\\x{1F475}\\x{1F3FE}|\\x{270D}\\x{1F3FD}|\\x{270C}\\x{1F3FF}|\\x{270D}\\x{1F3FB}|\\x{270D}\\x{1F3FC}|\\x{261D}\\x{1F3FD}|\\x{270D}\\x{1F3FE}|\\x{270D}\\x{1F3FF}|\\x{261D}\\x{1F3FF}|\\x{261D}\\x{1F3FE}|\\x{270C}\\x{1F3FD}|\\x{261D}\\x{1F3FC}|\\x{261D}\\x{1F3FB}|\\x{270C}\\x{1F3FE}|\\x{270B}\\x{1F3FC}|\\x{270C}\\x{1F3FC}|\\x{270C}\\x{1F3FB}|\\x{270B}\\x{1F3FF}|\\x{270B}\\x{1F3FE}|\\x{270B}\\x{1F3FD}|\\x{270B}\\x{1F3FB}|\\x{270A}\\x{1F3FF}|\\x{270A}\\x{1F3FE}|\\x{270A}\\x{1F3FD}|\\x{270A}\\x{1F3FC}|\\x{26F9}\\x{1F3FB}|\\x{270A}\\x{1F3FB}|\\x{26F9}\\x{1F3FC}|\\x{26F9}\\x{1F3FD}|\\x{1F004}\\x{FE0F}|\\x{26F9}\\x{1F3FF}|\\x{1F202}\\x{FE0F}|\\x{1F237}\\x{FE0F}|\\x{1F21A}\\x{FE0F}|\\x{1F22F}\\x{FE0F}|\\x{26F9}\\x{1F3FE}|\\x{1F170}\\x{FE0F}|\\x{1F3CB}\\x{FE0F}|\\x{1F171}\\x{FE0F}|\\x{1F17F}\\x{FE0F}|\\x{1F17E}\\x{FE0F}|\\x{1F575}\\x{FE0F}|\\x{1F3CC}\\x{FE0F}|\\x{1F3F3}\\x{FE0F}|\\x{269B}\\x{FE0F}|\\x{2699}\\x{FE0F}|\\x{269C}\\x{FE0F}|\\x{2697}\\x{FE0F}|\\x{2696}\\x{FE0F}|\\x{25AB}\\x{FE0F}|\\x{2694}\\x{FE0F}|\\x{2195}\\x{FE0F}|\\x{2196}\\x{FE0F}|\\x{26A1}\\x{FE0F}|\\x{2693}\\x{FE0F}|\\x{2197}\\x{FE0F}|\\x{267F}\\x{FE0F}|\\x{2198}\\x{FE0F}|\\x{267B}\\x{FE0F}|\\x{26A0}\\x{FE0F}|\\x{26BD}\\x{FE0F}|\\x{26AA}\\x{FE0F}|\\x{203C}\\x{FE0F}|\\x{26F9}\\x{FE0F}|\\x{26F5}\\x{FE0F}|\\x{26F3}\\x{FE0F}|\\x{26F2}\\x{FE0F}|\\x{26EA}\\x{FE0F}|\\x{26D4}\\x{FE0F}|\\x{00AE}\\x{FE0F}|\\x{2049}\\x{FE0F}|\\x{26AB}\\x{FE0F}|\\x{26C5}\\x{FE0F}|\\x{2122}\\x{FE0F}|\\x{2139}\\x{FE0F}|\\x{2194}\\x{FE0F}|\\x{26C4}\\x{FE0F}|\\x{26BE}\\x{FE0F}|\\x{26B1}\\x{FE0F}|\\x{26B0}\\x{FE0F}|\\x{2199}\\x{FE0F}|\\x{2666}\\x{FE0F}|\\x{2668}\\x{FE0F}|\\x{2611}\\x{FE0F}|\\x{21AA}\\x{FE0F}|\\x{231A}\\x{FE0F}|\\x{231B}\\x{FE0F}|\\x{2328}\\x{FE0F}|\\x{261D}\\x{FE0F}|\\x{2618}\\x{FE0F}|\\x{24C2}\\x{FE0F}|\\x{2615}\\x{FE0F}|\\x{2614}\\x{FE0F}|\\x{260E}\\x{FE0F}|\\x{2622}\\x{FE0F}|\\x{2604}\\x{FE0F}|\\x{2603}\\x{FE0F}|\\x{2602}\\x{FE0F}|\\x{2601}\\x{FE0F}|\\x{2600}\\x{FE0F}|\\x{25FE}\\x{FE0F}|\\x{25AA}\\x{FE0F}|\\x{25FC}\\x{FE0F}|\\x{25FB}\\x{FE0F}|\\x{25C0}\\x{FE0F}|\\x{2620}\\x{FE0F}|\\x{2623}\\x{FE0F}|\\x{25B6}\\x{FE0F}|\\x{264C}\\x{FE0F}|\\x{2665}\\x{FE0F}|\\x{2663}\\x{FE0F}|\\x{2660}\\x{FE0F}|\\x{2653}\\x{FE0F}|\\x{2652}\\x{FE0F}|\\x{2651}\\x{FE0F}|\\x{2650}\\x{FE0F}|\\x{264F}\\x{FE0F}|\\x{264E}\\x{FE0F}|\\x{264D}\\x{FE0F}|\\x{264B}\\x{FE0F}|\\x{2626}\\x{FE0F}|\\x{264A}\\x{FE0F}|\\x{2649}\\x{FE0F}|\\x{2648}\\x{FE0F}|\\x{263A}\\x{FE0F}|\\x{2639}\\x{FE0F}|\\x{2638}\\x{FE0F}|\\x{21A9}\\x{FE0F}|\\x{262F}\\x{FE0F}|\\x{262E}\\x{FE0F}|\\x{262A}\\x{FE0F}|\\x{25FD}\\x{FE0F}|\\x{2934}\\x{FE0F}|\\x{00A9}\\x{FE0F}|\\x{27A1}\\x{FE0F}|\\x{2B1C}\\x{FE0F}|\\x{2B1B}\\x{FE0F}|\\x{26FA}\\x{FE0F}|\\x{2B06}\\x{FE0F}|\\x{2B05}\\x{FE0F}|\\x{2935}\\x{FE0F}|\\x{2764}\\x{FE0F}|\\x{2B55}\\x{FE0F}|\\x{2763}\\x{FE0F}|\\x{2757}\\x{FE0F}|\\x{2747}\\x{FE0F}|\\x{2744}\\x{FE0F}|\\x{2734}\\x{FE0F}|\\x{2733}\\x{FE0F}|\\x{2B50}\\x{FE0F}|\\x{3030}\\x{FE0F}|\\x{271D}\\x{FE0F}|\\x{0033}\\x{20E3}|\\x{0039}\\x{20E3}|\\x{0038}\\x{20E3}|\\x{0037}\\x{20E3}|\\x{0036}\\x{20E3}|\\x{0035}\\x{20E3}|\\x{0034}\\x{20E3}|\\x{0032}\\x{20E3}|\\x{303D}\\x{FE0F}|\\x{0031}\\x{20E3}|\\x{0030}\\x{20E3}|\\x{002A}\\x{20E3}|\\x{0023}\\x{20E3}|\\x{3299}\\x{FE0F}|\\x{3297}\\x{FE0F}|\\x{2721}\\x{FE0F}|\\x{2B07}\\x{FE0F}|\\x{2716}\\x{FE0F}|\\x{2714}\\x{FE0F}|\\x{2712}\\x{FE0F}|\\x{26FD}\\x{FE0F}|\\x{2702}\\x{FE0F}|\\x{270F}\\x{FE0F}|\\x{270D}\\x{FE0F}|\\x{2708}\\x{FE0F}|\\x{270C}\\x{FE0F}|\\x{2709}\\x{FE0F}|\\x{1F988}|\\x{1F98B}|\\x{1F98A}|\\x{1F989}|\\x{1F91D}|\\x{1F91E}|\\x{1F920}|\\x{1F987}|\\x{1F986}|\\x{1F985}|\\x{1F984}|\\x{1F98D}|\\x{1F921}|\\x{1F98C}|\\x{1F91C}|\\x{1F98E}|\\x{1F98F}|\\x{1F990}|\\x{1F991}|\\x{1F9C0}|\\x{1F923}|\\x{1F942}|\\x{1F941}|\\x{1F940}|\\x{1F93E}|\\x{1F93D}|\\x{1F938}|\\x{1F93C}|\\x{1F93A}|\\x{1F3EE}|\\x{1F922}|\\x{1F983}|\\x{1F924}|\\x{1F95A}|\\x{1F94A}|\\x{1F95B}|\\x{1F94B}|\\x{1F950}|\\x{1F951}|\\x{1F952}|\\x{1F959}|\\x{1F949}|\\x{1F958}|\\x{1F957}|\\x{1F934}|\\x{1F953}|\\x{1F954}|\\x{1F935}|\\x{1F956}|\\x{1F933}|\\x{1F936}|\\x{1F925}|\\x{1F927}|\\x{1F926}|\\x{1F955}|\\x{1F982}|\\x{1F981}|\\x{1F980}|\\x{1F95E}|\\x{1F930}|\\x{1F948}|\\x{1F95D}|\\x{1F937}|\\x{1F943}|\\x{1F944}|\\x{1F945}|\\x{1F95C}|\\x{1F947}|\\x{1F939}|\\x{1F615}|\\x{1F91B}|\\x{1F400}|\\x{1F40A}|\\x{1F409}|\\x{1F408}|\\x{1F407}|\\x{1F406}|\\x{1F405}|\\x{1F404}|\\x{1F403}|\\x{1F402}|\\x{1F401}|\\x{1F3FF}|\\x{1F40C}|\\x{1F3FE}|\\x{1F3FD}|\\x{1F3FC}|\\x{1F3FB}|\\x{1F3FA}|\\x{1F3F9}|\\x{1F3F8}|\\x{1F3F7}|\\x{1F3F5}|\\x{1F3F4}|\\x{1F40B}|\\x{1F40D}|\\x{1F3F0}|\\x{1F41B}|\\x{1F425}|\\x{1F424}|\\x{1F423}|\\x{1F422}|\\x{1F421}|\\x{1F420}|\\x{1F41F}|\\x{1F41E}|\\x{1F41D}|\\x{1F41C}|\\x{1F41A}|\\x{1F40E}|\\x{1F419}|\\x{1F418}|\\x{1F417}|\\x{1F416}|\\x{1F415}|\\x{1F414}|\\x{1F413}|\\x{1F412}|\\x{1F411}|\\x{1F410}|\\x{1F40F}|\\x{1F3F3}|\\x{1F3EF}|\\x{1F427}|\\x{1F3C7}|\\x{1F3D1}|\\x{1F3D0}|\\x{1F3CF}|\\x{1F3CE}|\\x{1F3CD}|\\x{1F3CC}|\\x{1F3CB}|\\x{1F3CA}|\\x{1F3C9}|\\x{1F3C8}|\\x{1F3C6}|\\x{1F3D3}|\\x{1F3C5}|\\x{1F3C4}|\\x{1F3C3}|\\x{1F3C2}|\\x{1F3C1}|\\x{1F3C0}|\\x{1F3BF}|\\x{1F3BE}|\\x{1F3BD}|\\x{1F3BC}|\\x{1F3D2}|\\x{1F3D4}|\\x{1F3ED}|\\x{1F3E2}|\\x{1F3EC}|\\x{1F3EB}|\\x{1F3EA}|\\x{1F3E9}|\\x{1F3E8}|\\x{1F3E7}|\\x{1F3E6}|\\x{1F3E5}|\\x{1F3E4}|\\x{1F3E3}|\\x{1F3E1}|\\x{1F3D5}|\\x{1F3E0}|\\x{1F3DF}|\\x{1F3DE}|\\x{1F3DD}|\\x{1F3DC}|\\x{1F3DB}|\\x{1F3DA}|\\x{1F3D9}|\\x{1F3D8}|\\x{1F3D7}|\\x{1F3D6}|\\x{1F426}|\\x{1F428}|\\x{1F3BA}|\\x{1F46B}|\\x{1F475}|\\x{1F474}|\\x{1F473}|\\x{1F472}|\\x{1F471}|\\x{1F470}|\\x{1F46F}|\\x{1F46E}|\\x{1F46D}|\\x{1F46C}|\\x{1F46A}|\\x{1F477}|\\x{1F469}|\\x{1F468}|\\x{1F467}|\\x{1F466}|\\x{1F465}|\\x{1F464}|\\x{1F463}|\\x{1F462}|\\x{1F461}|\\x{1F460}|\\x{1F476}|\\x{1F478}|\\x{1F45E}|\\x{1F486}|\\x{1F490}|\\x{1F48F}|\\x{1F48E}|\\x{1F48D}|\\x{1F48C}|\\x{1F48B}|\\x{1F48A}|\\x{1F489}|\\x{1F488}|\\x{1F487}|\\x{1F485}|\\x{1F479}|\\x{1F484}|\\x{1F483}|\\x{1F482}|\\x{1F481}|\\x{1F480}|\\x{1F47F}|\\x{1F47E}|\\x{1F47D}|\\x{1F47C}|\\x{1F47B}|\\x{1F47A}|\\x{1F45F}|\\x{1F45D}|\\x{1F429}|\\x{1F436}|\\x{1F91A}|\\x{1F43F}|\\x{1F43E}|\\x{1F43D}|\\x{1F43C}|\\x{1F43B}|\\x{1F43A}|\\x{1F439}|\\x{1F438}|\\x{1F437}|\\x{1F435}|\\x{1F442}|\\x{1F434}|\\x{1F433}|\\x{1F432}|\\x{1F431}|\\x{1F430}|\\x{1F42F}|\\x{1F42E}|\\x{1F42D}|\\x{1F42C}|\\x{1F42B}|\\x{1F42A}|\\x{1F441}|\\x{1F443}|\\x{1F45C}|\\x{1F451}|\\x{1F45B}|\\x{1F45A}|\\x{1F459}|\\x{1F458}|\\x{1F457}|\\x{1F456}|\\x{1F455}|\\x{1F454}|\\x{1F453}|\\x{1F452}|\\x{1F450}|\\x{1F444}|\\x{1F44F}|\\x{1F44E}|\\x{1F44D}|\\x{1F44C}|\\x{1F44B}|\\x{1F44A}|\\x{1F449}|\\x{1F448}|\\x{1F447}|\\x{1F446}|\\x{1F445}|\\x{1F3BB}|\\x{1F3B9}|\\x{1F492}|\\x{1F320}|\\x{1F32C}|\\x{1F32B}|\\x{1F32A}|\\x{1F329}|\\x{1F328}|\\x{1F327}|\\x{1F326}|\\x{1F325}|\\x{1F324}|\\x{1F321}|\\x{1F31F}|\\x{1F32E}|\\x{1F31E}|\\x{1F31D}|\\x{1F31C}|\\x{1F31B}|\\x{1F31A}|\\x{1F319}|\\x{1F318}|\\x{1F317}|\\x{1F316}|\\x{1F315}|\\x{1F32D}|\\x{1F32F}|\\x{1F313}|\\x{1F33D}|\\x{1F347}|\\x{1F346}|\\x{1F345}|\\x{1F344}|\\x{1F343}|\\x{1F342}|\\x{1F341}|\\x{1F340}|\\x{1F33F}|\\x{1F33E}|\\x{1F33C}|\\x{1F330}|\\x{1F33B}|\\x{1F33A}|\\x{1F339}|\\x{1F338}|\\x{1F337}|\\x{1F336}|\\x{1F335}|\\x{1F334}|\\x{1F333}|\\x{1F332}|\\x{1F331}|\\x{1F314}|\\x{1F312}|\\x{1F349}|\\x{1F195}|\\x{1F232}|\\x{1F22F}|\\x{1F21A}|\\x{1F202}|\\x{1F201}|\\x{1F19A}|\\x{1F199}|\\x{1F198}|\\x{1F197}|\\x{1F196}|\\x{1F194}|\\x{1F234}|\\x{1F193}|\\x{1F192}|\\x{1F191}|\\x{1F18E}|\\x{1F17F}|\\x{1F17E}|\\x{1F171}|\\x{1F170}|\\x{1F0CF}|\\x{1F004}|\\x{1F233}|\\x{1F235}|\\x{1F311}|\\x{1F306}|\\x{1F310}|\\x{1F30F}|\\x{1F30E}|\\x{1F30D}|\\x{1F30C}|\\x{1F30B}|\\x{1F30A}|\\x{1F309}|\\x{1F308}|\\x{1F307}|\\x{1F305}|\\x{1F236}|\\x{1F304}|\\x{1F303}|\\x{1F302}|\\x{1F301}|\\x{1F300}|\\x{1F251}|\\x{1F250}|\\x{1F23A}|\\x{1F239}|\\x{1F238}|\\x{1F237}|\\x{1F348}|\\x{1F34A}|\\x{1F3B8}|\\x{1F38D}|\\x{1F39A}|\\x{1F399}|\\x{1F397}|\\x{1F396}|\\x{1F393}|\\x{1F392}|\\x{1F391}|\\x{1F390}|\\x{1F38F}|\\x{1F38E}|\\x{1F38C}|\\x{1F39E}|\\x{1F38B}|\\x{1F38A}|\\x{1F389}|\\x{1F388}|\\x{1F387}|\\x{1F386}|\\x{1F385}|\\x{1F384}|\\x{1F383}|\\x{1F382}|\\x{1F39B}|\\x{1F39F}|\\x{1F380}|\\x{1F3AD}|\\x{1F3B7}|\\x{1F3B6}|\\x{1F3B5}|\\x{1F3B4}|\\x{1F3B3}|\\x{1F3B2}|\\x{1F3B1}|\\x{1F3B0}|\\x{1F3AF}|\\x{1F3AE}|\\x{1F3AC}|\\x{1F3A0}|\\x{1F3AB}|\\x{1F3AA}|\\x{1F3A9}|\\x{1F3A8}|\\x{1F3A7}|\\x{1F3A6}|\\x{1F3A5}|\\x{1F3A4}|\\x{1F3A3}|\\x{1F3A2}|\\x{1F3A1}|\\x{1F381}|\\x{1F37F}|\\x{1F34B}|\\x{1F358}|\\x{1F362}|\\x{1F361}|\\x{1F360}|\\x{1F35F}|\\x{1F35E}|\\x{1F35D}|\\x{1F35C}|\\x{1F35B}|\\x{1F35A}|\\x{1F359}|\\x{1F357}|\\x{1F364}|\\x{1F356}|\\x{1F355}|\\x{1F354}|\\x{1F353}|\\x{1F352}|\\x{1F351}|\\x{1F350}|\\x{1F34F}|\\x{1F34E}|\\x{1F34D}|\\x{1F34C}|\\x{1F363}|\\x{1F365}|\\x{1F37E}|\\x{1F373}|\\x{1F37D}|\\x{1F37C}|\\x{1F37B}|\\x{1F37A}|\\x{1F379}|\\x{1F378}|\\x{1F377}|\\x{1F376}|\\x{1F375}|\\x{1F374}|\\x{1F372}|\\x{1F366}|\\x{1F371}|\\x{1F370}|\\x{1F36F}|\\x{1F36E}|\\x{1F36D}|\\x{1F36C}|\\x{1F36B}|\\x{1F36A}|\\x{1F369}|\\x{1F368}|\\x{1F367}|\\x{1F491}|\\x{1F440}|\\x{1F493}|\\x{1F625}|\\x{1F62F}|\\x{1F62E}|\\x{1F62D}|\\x{1F62C}|\\x{1F62B}|\\x{1F62A}|\\x{1F629}|\\x{1F628}|\\x{1F627}|\\x{1F626}|\\x{1F624}|\\x{1F631}|\\x{1F623}|\\x{1F622}|\\x{1F621}|\\x{1F620}|\\x{1F61F}|\\x{1F61E}|\\x{1F61D}|\\x{1F61C}|\\x{1F61B}|\\x{1F61A}|\\x{1F630}|\\x{1F632}|\\x{1F618}|\\x{1F640}|\\x{1F64A}|\\x{1F649}|\\x{1F648}|\\x{1F647}|\\x{1F646}|\\x{1F645}|\\x{1F644}|\\x{1F643}|\\x{1F642}|\\x{1F641}|\\x{1F63F}|\\x{1F633}|\\x{1F63E}|\\x{1F63D}|\\x{1F63C}|\\x{1F63B}|\\x{1F63A}|\\x{1F639}|\\x{1F638}|\\x{1F637}|\\x{1F636}|\\x{1F494}|\\x{1F634}|\\x{1F619}|\\x{1F617}|\\x{1F64C}|\\x{1F5D1}|\\x{1F5F3}|\\x{1F5EF}|\\x{1F5E8}|\\x{1F5E3}|\\x{1F5E1}|\\x{1F5DE}|\\x{1F5DD}|\\x{1F5DC}|\\x{1F5D3}|\\x{1F5D2}|\\x{1F5C4}|\\x{1F5FB}|\\x{1F5C3}|\\x{1F5C2}|\\x{1F5BC}|\\x{1F5B2}|\\x{1F5B1}|\\x{1F5A8}|\\x{1F5A5}|\\x{1F5A4}|\\x{1F596}|\\x{1F595}|\\x{1F5FA}|\\x{1F5FC}|\\x{1F616}|\\x{1F60A}|\\x{1F614}|\\x{1F613}|\\x{1F612}|\\x{1F611}|\\x{1F610}|\\x{1F60F}|\\x{1F60E}|\\x{1F60D}|\\x{1F60C}|\\x{1F60B}|\\x{1F609}|\\x{1F5FD}|\\x{1F608}|\\x{1F607}|\\x{1F606}|\\x{1F605}|\\x{1F604}|\\x{1F603}|\\x{1F602}|\\x{1F601}|\\x{1F600}|\\x{1F5FF}|\\x{1F5FE}|\\x{1F64B}|\\x{1F64D}|\\x{1F58D}|\\x{1F6C0}|\\x{1F6CF}|\\x{1F6CE}|\\x{1F6CD}|\\x{1F6CC}|\\x{1F6CB}|\\x{1F6C5}|\\x{1F6C4}|\\x{1F6C3}|\\x{1F6C2}|\\x{1F6C1}|\\x{1F6BF}|\\x{1F6D1}|\\x{1F6BE}|\\x{1F6BD}|\\x{1F6BC}|\\x{1F6BB}|\\x{1F6BA}|\\x{1F6B9}|\\x{1F6B8}|\\x{1F6B7}|\\x{1F6B6}|\\x{1F6B5}|\\x{1F6D0}|\\x{1F6D2}|\\x{1F6B3}|\\x{1F6F6}|\\x{1F919}|\\x{1F918}|\\x{1F917}|\\x{1F916}|\\x{1F915}|\\x{1F914}|\\x{1F913}|\\x{1F912}|\\x{1F911}|\\x{1F910}|\\x{1F6F5}|\\x{1F6E0}|\\x{1F6F4}|\\x{1F6F3}|\\x{1F6F0}|\\x{1F6EC}|\\x{1F6EB}|\\x{1F6E9}|\\x{1F6E5}|\\x{1F6E4}|\\x{1F6E3}|\\x{1F6E2}|\\x{1F6E1}|\\x{1F6B4}|\\x{1F6B2}|\\x{1F64E}|\\x{1F68B}|\\x{1F695}|\\x{1F694}|\\x{1F693}|\\x{1F692}|\\x{1F691}|\\x{1F690}|\\x{1F68F}|\\x{1F68E}|\\x{1F68D}|\\x{1F68C}|\\x{1F68A}|\\x{1F697}|\\x{1F689}|\\x{1F688}|\\x{1F687}|\\x{1F686}|\\x{1F685}|\\x{1F684}|\\x{1F683}|\\x{1F682}|\\x{1F681}|\\x{1F680}|\\x{1F64F}|\\x{1F696}|\\x{1F698}|\\x{1F6B1}|\\x{1F6A6}|\\x{1F6B0}|\\x{1F6AF}|\\x{1F6AE}|\\x{1F6AD}|\\x{1F6AC}|\\x{1F6AB}|\\x{1F6AA}|\\x{1F6A9}|\\x{1F6A8}|\\x{1F6A7}|\\x{1F6A5}|\\x{1F699}|\\x{1F6A4}|\\x{1F6A3}|\\x{1F6A2}|\\x{1F6A1}|\\x{1F6A0}|\\x{1F69F}|\\x{1F69E}|\\x{1F69D}|\\x{1F69C}|\\x{1F69B}|\\x{1F69A}|\\x{1F590}|\\x{1F635}|\\x{1F58C}|\\x{1F4D6}|\\x{1F4E0}|\\x{1F4DF}|\\x{1F4DE}|\\x{1F4DD}|\\x{1F4DC}|\\x{1F4DB}|\\x{1F4DA}|\\x{1F4D9}|\\x{1F4D8}|\\x{1F4D7}|\\x{1F4D5}|\\x{1F4E2}|\\x{1F4D4}|\\x{1F4D3}|\\x{1F4D2}|\\x{1F4D1}|\\x{1F4D0}|\\x{1F4CF}|\\x{1F4CE}|\\x{1F4CD}|\\x{1F4CC}|\\x{1F4CB}|\\x{1F4E1}|\\x{1F4E3}|\\x{1F4C9}|\\x{1F4F1}|\\x{1F4FB}|\\x{1F4FA}|\\x{1F4F9}|\\x{1F4F8}|\\x{1F4F7}|\\x{1F4F6}|\\x{1F4F5}|\\x{1F4F4}|\\x{1F4F3}|\\x{1F4F2}|\\x{1F4F0}|\\x{1F4E4}|\\x{1F4EF}|\\x{1F4EE}|\\x{1F4ED}|\\x{1F4EC}|\\x{1F4EB}|\\x{1F4EA}|\\x{1F4E9}|\\x{1F4E8}|\\x{1F4E7}|\\x{1F4E6}|\\x{1F4E5}|\\x{1F4CA}|\\x{1F4C8}|\\x{1F4FD}|\\x{1F4A1}|\\x{1F58B}|\\x{1F4AA}|\\x{1F4A9}|\\x{1F4A8}|\\x{1F4A7}|\\x{1F4A6}|\\x{1F4A5}|\\x{1F4A4}|\\x{1F4A3}|\\x{1F4A2}|\\x{1F4A0}|\\x{1F4AD}|\\x{1F49F}|\\x{1F49E}|\\x{1F49D}|\\x{1F49C}|\\x{1F49B}|\\x{1F49A}|\\x{1F499}|\\x{1F498}|\\x{1F497}|\\x{1F496}|\\x{1F495}|\\x{1F4AC}|\\x{1F4AE}|\\x{1F4C7}|\\x{1F4BC}|\\x{1F4C6}|\\x{1F4C5}|\\x{1F4C4}|\\x{1F4C3}|\\x{1F4C2}|\\x{1F4C1}|\\x{1F4C0}|\\x{1F4BF}|\\x{1F4BE}|\\x{1F4BD}|\\x{1F4BB}|\\x{1F4AF}|\\x{1F4BA}|\\x{1F4B9}|\\x{1F4B8}|\\x{1F4B7}|\\x{1F4B6}|\\x{1F4B5}|\\x{1F4B4}|\\x{1F4B3}|\\x{1F4B2}|\\x{1F4B1}|\\x{1F4B0}|\\x{1F4FC}|\\x{1F4AB}|\\x{1F4FF}|\\x{1F54D}|\\x{1F558}|\\x{1F557}|\\x{1F556}|\\x{1F555}|\\x{1F554}|\\x{1F553}|\\x{1F552}|\\x{1F551}|\\x{1F550}|\\x{1F54E}|\\x{1F54C}|\\x{1F55A}|\\x{1F54B}|\\x{1F54A}|\\x{1F549}|\\x{1F53D}|\\x{1F53C}|\\x{1F53B}|\\x{1F53A}|\\x{1F539}|\\x{1F538}|\\x{1F537}|\\x{1F559}|\\x{1F55B}|\\x{1F535}|\\x{1F573}|\\x{1F500}|\\x{1F58A}|\\x{1F587}|\\x{1F57A}|\\x{1F579}|\\x{1F578}|\\x{1F577}|\\x{1F576}|\\x{1F575}|\\x{1F574}|\\x{1F570}|\\x{1F55C}|\\x{1F567}|\\x{1F566}|\\x{1F565}|\\x{1F564}|\\x{1F563}|\\x{1F562}|\\x{1F561}|\\x{1F560}|\\x{1F55F}|\\x{1F55E}|\\x{1F55D}|\\x{1F536}|\\x{1F56F}|\\x{1F534}|\\x{1F50D}|\\x{1F517}|\\x{1F516}|\\x{1F515}|\\x{1F514}|\\x{1F513}|\\x{1F512}|\\x{1F511}|\\x{1F510}|\\x{1F50F}|\\x{1F50E}|\\x{1F50C}|\\x{1F519}|\\x{1F50B}|\\x{1F50A}|\\x{1F509}|\\x{1F508}|\\x{1F506}|\\x{1F505}|\\x{1F504}|\\x{1F503}|\\x{1F502}|\\x{1F533}|\\x{1F501}|\\x{1F518}|\\x{1F507}|\\x{1F51A}|\\x{1F527}|\\x{1F531}|\\x{1F51B}|\\x{1F532}|\\x{1F530}|\\x{1F52F}|\\x{1F52E}|\\x{1F52C}|\\x{1F52B}|\\x{1F52A}|\\x{1F529}|\\x{1F528}|\\x{1F52D}|\\x{1F51D}|\\x{1F51C}|\\x{1F51E}|\\x{1F526}|\\x{1F51F}|\\x{1F521}|\\x{1F520}|\\x{1F522}|\\x{1F523}|\\x{1F524}|\\x{1F525}|\\x{262F}|\\x{2620}|\\x{262E}|\\x{262A}|\\x{2626}|\\x{2623}|\\x{2622}|\\x{2602}|\\x{2614}|\\x{261D}|\\x{2618}|\\x{2615}|\\x{2611}|\\x{260E}|\\x{2604}|\\x{2639}|\\x{2603}|\\x{2638}|\\x{2650}|\\x{263A}|\\x{2651}|\\x{2668}|\\x{2600}|\\x{2666}|\\x{2665}|\\x{2663}|\\x{2660}|\\x{2653}|\\x{2652}|\\x{264F}|\\x{2640}|\\x{264E}|\\x{264D}|\\x{264C}|\\x{264B}|\\x{264A}|\\x{2649}|\\x{2648}|\\x{2642}|\\x{2601}|\\x{2328}|\\x{25FE}|\\x{2197}|\\x{23CF}|\\x{231B}|\\x{231A}|\\x{21AA}|\\x{21A9}|\\x{2199}|\\x{2198}|\\x{2196}|\\x{23EA}|\\x{2195}|\\x{2194}|\\x{2139}|\\x{2122}|\\x{2049}|\\x{203C}|\\x{00AE}|\\x{267F}|\\x{23E9}|\\x{23EB}|\\x{25FD}|\\x{23FA}|\\x{25FC}|\\x{25FB}|\\x{25C0}|\\x{25B6}|\\x{25AB}|\\x{25AA}|\\x{24C2}|\\x{23F9}|\\x{23EC}|\\x{23F8}|\\x{23F3}|\\x{23F2}|\\x{23F1}|\\x{23F0}|\\x{23EF}|\\x{23EE}|\\x{23ED}|\\x{267B}|\\x{2728}|\\x{2692}|\\x{2744}|\\x{2757}|\\x{2755}|\\x{2754}|\\x{2753}|\\x{274E}|\\x{274C}|\\x{2747}|\\x{2734}|\\x{2764}|\\x{2733}|\\x{2721}|\\x{271D}|\\x{2716}|\\x{2714}|\\x{2712}|\\x{270F}|\\x{270D}|\\x{2763}|\\x{2795}|\\x{270B}|\\x{2B1B}|\\x{3299}|\\x{3297}|\\x{303D}|\\x{3030}|\\x{2B55}|\\x{2B50}|\\x{2B1C}|\\x{2B07}|\\x{2796}|\\x{2B06}|\\x{2B05}|\\x{2935}|\\x{2934}|\\x{27BF}|\\x{27B0}|\\x{27A1}|\\x{2797}|\\x{270C}|\\x{270A}|\\x{2693}|\\x{26AA}|\\x{26C5}|\\x{26C4}|\\x{26BE}|\\x{26BD}|\\x{26B1}|\\x{26B0}|\\x{26AB}|\\x{26A1}|\\x{26CE}|\\x{26A0}|\\x{269C}|\\x{269B}|\\x{2699}|\\x{2697}|\\x{2696}|\\x{2695}|\\x{2694}|\\x{26C8}|\\x{26CF}|\\x{2709}|\\x{26F5}|\\x{2708}|\\x{2705}|\\x{2702}|\\x{26FD}|\\x{26FA}|\\x{26F9}|\\x{26F8}|\\x{26F7}|\\x{26F4}|\\x{26D1}|\\x{26F3}|\\x{26F2}|\\x{26F1}|\\x{26F0}|\\x{26EA}|\\x{26E9}|\\x{26D4}|\\x{26D3}|\\x{00A9}/u";
        $replaced = 0;
        $message = preg_replace($emoji_pattern, '', $str, -1, $replaced);

        return $replaced ? $message : $str;
    }

    /**
     * @param string $text
     * @return void
     */
    public function addNote($text)
    {
        $note = $this->customerNoteFactory->create()->load($this->getCustomerId());
        $note->setCustomerId($this->getCustomerId());
        $note->setCustomerNote($text);
        $note->save();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) 
     * @SuppressWarnings(PHPMD.NPathComplexity) @fixme
     *
     * @return void
     */
    protected function updateFields()
    {
        $config = $this->config;

        if (!$this->getPriorityId()) {
            $priorityIds = $this->priorityCollectionFactory->create()->getAllIds();
            if (in_array($config->getDefaultPriority(), $priorityIds)) {
                $this->setPriorityId($config->getDefaultPriority());
            } else {
                $this->setPriorityId(null);
            }
        }

        if (!$this->getStatusId()) {
            $this->setStatusId($config->getDefaultStatus());
        }

        if (!$this->getCode()) {
            $this->setCode($this->helpdeskString->generateTicketCode());
        }

        if (!$this->getExternalId()) {
            $this->setExternalId(md5($this->getCode() . $this->helpdeskString->generateRandNum(10)));
        }

        if ($this->getCustomerId() > 0) {
            $customer = $this->customerFactory->create();
            $customer->load($this->getCustomerId());
            //we don't change the email, because customer can send the ticket from another email (not from registered)
            //maybe we don't need this if??
            if (!$this->getCustomerEmail()) {
                $this->setCustomerEmail($customer->getEmail());
            }
            $this->setCustomerName($customer->getName());
        }

        if (!$this->getFirstSolvedAt() && $this->isClosed()) {
            $this->setFirstSolvedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }

        if (in_array($this->getStatusId(), $config->getGeneralArchivedStatusList()) &&
            $this->getFolder() != Config::FOLDER_SPAM
        ) {
            $this->setFolder(Config::FOLDER_ARCHIVE);
        }
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        $this->updateFields();

        if ($this->getData('user_id') && ($this->getOrigData('user_id') != $this->getData('user_id'))) {
            $this->helpdeskRuleevent->newEvent(Config::RULE_EVENT_TICKET_ASSIGNED, $this);
        }
        $this->helpdeskRuleevent->newEvent(Config::RULE_EVENT_TICKET_UPDATED, $this);

        if (!$this->getStoreId()) {
            $this->setStoreId($this->storeManager->getDefaultStoreView()->getStoreId());
        }

        return parent::beforeSave();
    }

    /**
     * @return $this
     */
    public function afterSave()
    {
        if (
            !$this->getIsSent() && (int)$this->getData('user_id') &&
            ($this->getOrigData('user_id') != $this->getData('user_id')) &&
            $this->getData('message_sender') != $this->getData('user_id')
        ) {
            $this->setIsSent(true);
            if (!$this->getIsMigration()) {
                $this->helpdeskNotification->notifyUserAssign($this);
            }
        }

        return parent::afterSave();
    }

    /**
     * Overridden superclass function. Deletes all emails linked with current ticket
     *
     * @return $this
     */
    public function beforeDelete()
    {
        $ticketId     = $this->getId();
        $connection   = $this->resourceConnection->getConnection();
        $messageTable = $this->resourceConnection->getTableName('mst_helpdesk_message');
        $sql          = "DELETE  FROM $messageTable WHERE ticket_id = $ticketId";
        $connection->query($sql);

        return parent::beforeDelete();
    }

    /**
     * {@inheritdoc}
     */
    public function afterDelete()
    {
        $return = parent::afterDelete();

        $this->ticketManagement->logTicketDeletion($this);

        return $return;
    }

    /**
     * @param bool|true $useTicketsStore
     * @return string
     */
    public function getUrl($useTicketsStore = true)
    {
        $urlManager = $this->urlManager;
        if ($useTicketsStore && $this->getStoreId()) {
            $urlManager->setScope($this->getStoreId());
        }
        $url = $urlManager->getUrl(
            'helpdesk/ticket/view',
            ['id' => $this->getId(), '_nosid' => true]
        );

        return $url;
    }

    /**
     * @param bool|true $useTicketsStore
     * @return string
     */
    public function getExternalUrl($useTicketsStore = true)
    {
        if (!$this->config->getGeneralIsAllowExternalURLs()) {
            return $this->getUrl($useTicketsStore);
        }
        /*
         * removed '_store' => $this->getStoreId() from url.
         * if necessary, it's better to create redirect (or use some other way)
         */
        $urlManager = $this->urlManager;
        if ($useTicketsStore && $this->getStoreId()) {
            $urlManager->setScope($this->getStoreId());
        }
        $url = $urlManager->getUrl(
            'helpdesk/ticket/external',
            [
                'id' => $this->getExternalId(),
                '_nosid' => true,
                \Magento\Backend\Model\UrlInterface::SECRET_KEY_PARAM_NAME => ''
            ]
        );

        return $url;
    }

    /**
     * @return string
     */
    public function getStopRemindUrl()
    {
        $url = $this->urlManager->getUrl(
            'helpdesk/ticket/stopremind',
            ['id' => $this->getExternalId(), '_nosid' => true]
        );

        return $url;
    }

    /**
     * @return string
     */
    public function getBackendUrl()
    {
        $url = $this->backendUrlManager->getUrl(
            'helpdesk/ticket/edit',
            ['id' => $this->getId(), '_nosid' => true]
        );

        return $url;
    }

    /**
     * @param bool $includePrivate
     *
     * @return ResourceModel\Message\Collection | \Mirasvit\Helpdesk\Model\Message[]
     */
    public function getMessages($includePrivate = false)
    {
        $collection = $this->messageCollectionFactory->create();
        $collection
            ->addFieldToFilter('ticket_id', $this->getId())
            ->setOrder('created_at', 'desc');
        if (!$includePrivate) {
            $collection->addFieldToFilter(
                'type',
                [
                    ['eq' => ''],
                    ['eq' => Config::MESSAGE_PUBLIC],
                    ['eq' => Config::MESSAGE_PUBLIC_THIRD],
                ]
            );
        }

        return $collection;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Message
     */
    public function getLastMessage()
    {
        $collection = $this->messageCollectionFactory->create();
        $collection
            ->addFieldToFilter('ticket_id', $this->getId())
            ->setOrder('message_id', 'asc');

        return $collection->getLastItem();
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Message
     */
    public function getLastPublicMessage()
    {
        $collection = $this->messageCollectionFactory->create();
        $collection
            ->addFieldToFilter('ticket_id', $this->getId())
            ->addFieldToFilter('type', ['nin' => [Config::MESSAGE_INTERNAL, Config::MESSAGE_INTERNAL_THIRD]])
            ->setOrder('message_id', 'asc');

        return $collection->getLastItem();
    }

    /**
     * @return string
     */
    public function getLastReplyName()
    {
        if ($this->config->getGeneralSignTicketBy() == Config::SIGN_TICKET_BY_DEPARTMENT) {
            $message = $this->getLastPublicMessage();

            if ($message->getTriggeredBy() == Config::CUSTOMER) {
                return $message->getCustomerName();
            }

            if ($message->getTriggeredBy() == Config::USER) {
                return $message->getFrontendUserName();
            }

            if ($message->getTriggeredBy() == Config::THIRD) {
                return $message->getThirdPartyName();
            }
        }

        return parent::getLastReplyName();
    }

    /**
     * @return string
     */
    public function getLastMessageHtmlText()
    {
        if ($this->getAllowSendInternal()) {
            return $this->getLastMessage()->getUnsafeBodyHtml();
        } else {
            return $this->getLastPublicMessage()->getUnsafeBodyHtml();
        }
    }

    /**
     * @return string
     */
    public function getLastMessagePlainText()
    {
        if ($this->getAllowSendInternal()) {
            return $this->getLastMessage()->getBodyPlain();
        } else {
            return $this->getLastPublicMessage()->getBodyPlain();
        }
    }

    /**
     * @param int|string $format
     * @return string
     */
    public function getCreatedAtFormated($format = \IntlDateFormatter::LONG)
    {
        if (!is_int($format)) {
            $format = $this->decodeDateFormat($format);
        }
        $date = new \DateTime($this->getCreatedAt());

        return $this->localeDate->formatDateTime($date, $format);
    }

    /**
     * @param int|string $format
     * @return string
     */
    public function getUpdatedAtFormated($format = \IntlDateFormatter::LONG)
    {
        if (!is_int($format)) {
            $format = $this->decodeDateFormat($format);
        }
        $date = new \DateTime($this->getUpdatedAt());

        return $this->localeDate->formatDateTime($date, $format);
    }

    /**
     * @param string $format
     *
     * @return int
     */
    private function decodeDateFormat($format)
    {
        switch ($format) {
            case 'none':
                $format = \IntlDateFormatter::NONE;
                break;
            case 'full':
                $format = \IntlDateFormatter::FULL;
                break;
            case 'long':
                $format = \IntlDateFormatter::LONG;
                break;
            case 'medium':
                $format = \IntlDateFormatter::MEDIUM;
                break;
            case 'short':
                $format = \IntlDateFormatter::SHORT;
                break;
            case 'traditional':
                $format = \IntlDateFormatter::TRADITIONAL;
                break;
            case 'gregorian':
                $format = \IntlDateFormatter::GREGORIAN;
                break;
            default:
                $format = \IntlDateFormatter::LONG;
        }

        return $format;
    }

    /**
     *
     */
    public function open()
    {
        $status = $this->statusFactory->create()->loadByCode(Config::STATUS_OPEN);
        $this->setStatusId($status->getId())->save();
    }

    /**
     *
     */
    public function close()
    {
        $status = $this->statusFactory->create()->loadByCode(Config::STATUS_CLOSED);
        $this->setStatusId($status->getId())->save();
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        $status = $this->statusFactory->create()->loadByCode(Config::STATUS_CLOSED);
        if ($status && $status->getId() == $this->getStatusId()) {
            return true;
        }

        return false;
    }

    /**
     * @param string       $value
     * @param string|false $prefix
     * @return $this
     */
    public function initOwner($value, $prefix = false)
    {
        //set ticket user and department
        if ($value) {
            $owner = $value;
            $owner = explode('_', $owner);
            if ($prefix) {
                $prefix .= '_';
            }
            $this->setData($prefix . 'department_id', (int)$owner[0]);
            $this->setData($prefix . 'user_id', (int)$owner[1]);
        }

        return $this;
    }

    /**
     *
     */
    public function markAsSpam()
    {
        $this->setFolder(Config::FOLDER_SPAM)->save();
    }

    /**
     *
     */
    public function markAsNotSpam()
    {
        $this->setFolder(Config::FOLDER_INBOX)->save();
        if ($emailId = $this->getEmailId()) {
            $email = $this->emailFactory->create()->load($emailId);
            $email->setPatternId(0)->save();
        }
    }

    /**
     * @var \Magento\Customer\Model\Customer|\Magento\Framework\DataObject|false|null
     */
    protected $customer = null;

    /**
     * @return \Magento\Customer\Model\Customer|\Magento\Framework\DataObject|false
     */
    public function getCustomer()
    {
        if ($this->customer === null) {
            if ($this->getCustomerId()) {
                $this->customer = $this->customerFactory->create()->load($this->getCustomerId());
            } elseif ($this->getCustomerEmail()) {
                $this->customer = new \Magento\Framework\DataObject([
                    'name'  => $this->getCustomerName(),
                    'email' => $this->getCustomerEmail(),
                ]);
            } else {
                $this->customer = false;
            }
        }

        return $this->customer;
    }

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order = null;

    /**
     * @return \Magento\Sales\Model\Order|false
     */
    public function getOrder()
    {
        if (!$this->getOrderId()) {
            return false;
        }
        if ($this->order === null) {
            $this->order = $this->orderFactory->create()->load($this->getOrderId());
        }

        return $this->order;
    }

    /**
     * @param string $subject
     * @return string
     */
    public function getEmailSubject($subject = '')
    {
        $subject = __($subject)->render();
        if ($this->getEmailSubjectPrefix()) {
            $subject = $this->getEmailSubjectPrefix() . $subject;
        }

        return (string)$this->helpdeskEmail->getEmailSubject($this, $subject);
    }

    /**
     * @return string
     */
    public function getHiddenCodeHtml()
    {
        if (!$this->config->getNotificationIsShowCode()) {
            return $this->helpdeskEmail->getHiddenCode($this->getCode());
        }

        return '';
    }

    /**
     * @return string
     */
    public function getHistoryHtml()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        if ($this->getUser()) {
            return $this->getUser()->getName();
        }

        return '';
    }

    /**
     * @return ResourceModel\Tag\Collection|Tag[]
     */
    public function getTags()
    {
        $tags = [0];
        if (is_array($this->getTagIds())) {
            $tags = array_merge($tags, $this->getTagIds());
        }
        $collection = $this->tagCollectionFactory->create()
            ->addFieldToFilter('tag_id', $tags);

        return $collection;
    }

    /**
     *
     */
    public function loadTagIds()
    {
        if ($this->getData('tag_ids') === null) {
            $this->getResource()->loadTagIds($this);
        }
    }

    /**
     * @return bool
     */
    public function hasCustomer()
    {
        return $this->getCustomerId() > 0 || $this->getQuoteAddressId() > 0;
    }

    /**
     * @param int $orderId
     *
     * @return $this
     */
    public function initFromOrder($orderId)
    {
        $this->setOrderId($orderId);
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->getOrder();
        /** @var \Magento\Sales\Model\Order\Address $address */
        $address = ($order->getShippingAddress()) ? $order->getShippingAddress() : $order->getBillingAddress();

        $this->setQuoteAddressId($address->getId());
        $this->setCustomerId($order->getCustomerId());
        $this->setStoreId($order->getStoreId());

        if ($this->getCustomerId()) {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $this->getCustomer();
            $this->setCustomerEmail($customer->getEmail());
        } elseif ($order->getCustomerEmail()) {
            $this->setCustomerEmail($order->getCustomerEmail());
        } else {
            $this->setCustomerEmail($address->getEmail());
        }

        return $this;
    }

    /**
     * @param string $fromEmail
     *
     * @return bool
     */
    public function isThirdPartyPublic($fromEmail)
    {
        $collection = $this->messageCollectionFactory->create();
        $collection
            ->addFieldToFilter('ticket_id', $this->getId())
            ->addFieldToFilter('triggered_by', Config::USER)
            ->addFieldToFilter('third_party_email', $fromEmail)
            ->setOrder('message_id', 'asc');

        $message = $collection->getLastItem();

        if ($message->getType() == Config::MESSAGE_INTERNAL_THIRD) {
            return false;
        }

        return true;
    }

    /************************/

    /**
     * Returns owner id. E.g. "1_0" or "2_3".
     *
     * @return string
     */
    public function getOwner()
    {
        return (int)$this->getDepartmentId() . '_' . (int)$this->getUserId();
    }

    /**
     * Adds a text to search index (without ticket saving).
     *
     * @param string $text
     *
     * @return void
     */
    public function addToSearchIndex($text)
    {
        $index = $this->getSearchIndex();
        $newWords = explode(' ', (string)$text);
        $oldWords = explode(' ', (string)$index);
        $words = array_unique(array_merge($newWords, $oldWords));
        $this->setSearchIndex(implode(' ', $words));
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getState()
    {
        $data = $this->getData();
        $data['folder_name'] = $this->getFolderName();

        return new \Magento\Framework\DataObject($data);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getFolderName()
    {
        $folders = [
            ''                     => '', // if folder not set
            Config::FOLDER_INBOX   => __('Inbox'),
            Config::FOLDER_ARCHIVE => __('Archive'),
            Config::FOLDER_SPAM    => __('Spam')
        ];

        return $folders[$this->getFolder()];
    }
}
