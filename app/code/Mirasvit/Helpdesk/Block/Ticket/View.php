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



namespace Mirasvit\Helpdesk\Block\Ticket;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Helper\Field;
use Mirasvit\Helpdesk\Helper\Order;
use Mirasvit\Helpdesk\Model\Config;
use Mirasvit\Helpdesk\Model\Message;
use Magento\Framework\Message\ManagerInterface;
use Mirasvit\Helpdesk\Helper\Notification;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\User\Model\User[]
     */
    private   $users;

    protected $helpdeskField;

    protected $registry;

    protected $context;

    protected $helpdeskOrder;

    protected $config;

    protected $messageManager;

    protected $helpdeskNotification;

    public function __construct(
        Field            $helpdeskField,
        Registry         $registry,
        Context          $context,
        Order            $helpdeskOrder,
        Config           $config,
        ManagerInterface $messageManager,
        Notification     $helpdeskNotification,
        array            $data = []
    ) {
        $this->helpdeskField        = $helpdeskField;
        $this->registry             = $registry;
        $this->helpdeskOrder        = $helpdeskOrder;
        $this->config               = $config;
        $this->context              = $context;
        $this->messageManager       = $messageManager;
        $this->helpdeskNotification = $helpdeskNotification;

        parent::__construct($context, $data);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $ticket = $this->getTicket();
        $this->pageConfig->getTitle()->set(__('[%1] %2', $ticket->getCode(), $ticket->getSubject()));
        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            /** @var \Magento\Theme\Block\Html\Title $pageMainTitle */
            $pageMainTitle->setPageTitle(__($ticket->getSubject()));
        }
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Ticket
     */
    public function getTicket()
    {
        return $this->registry->registry('current_ticket');
    }

    /**
     * @return View\Summary\DefaultRow[]
     */
    public function getSummary()
    {
        $rows = [];

        $names = array_intersect($this->getChildNames(), $this->getGroupChildNames('summary'));

        foreach ($names as $name) {
            /** @var View\Summary\DefaultRow $row */
            $row = $this->getChildBlock($name);
            $rows[$name] = $row;
        }

        return $rows;
    }

    /**
     * @param View\Summary\DefaultRow $row
     * @param TicketInterface         $item
     *
     * @return string
     */
    public function getSummaryHtml(View\Summary\DefaultRow $row, TicketInterface $item)
    {
        $row->setItem($item);

        return $row->toHtml();
    }

    /**
     * @return string
     */
    public function getPostUrl()
    {
        $ticket = $this->getTicket();
        if ($this->registry->registry('external_ticket')) {
            return $this->context->getUrlBuilder()->getUrl(
                'helpdesk/ticket/postexternal',
                ['id' => $ticket->getExternalId()]
            );
        } else {
            return $this->context->getUrlBuilder()->getUrl('helpdesk/ticket/postmessage', ['id' => $ticket->getId()]);
        }
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getCustomFields()
    {
        $collection = $this->helpdeskField->getVisibleCustomerCollection();

        return $collection;
    }

    /**
     * @return \Mirasvit\Helpdesk\Helper\Order
     */
    public function getHelpdeskData()
    {
        return $this->helpdeskOrder;
    }

    /**
     * @return \Mirasvit\Helpdesk\Helper\Field
     */
    public function getHelpdeskField()
    {
        return $this->helpdeskField;
    }

    /**
     * @return bool
     */
    public function isExternal()
    {
        return $this->getRequest()->getActionName() == 'external';
    }

    /**
     * Escape HTML entities
     *
     * @param string|array $data
     * @param array|null   $allowedTags
     *
     * @return string|string[]
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        //html can contain incorrect symbols which produce warrnings to log
        $internalErrors = libxml_use_internal_errors(true);
        $res            = parent::escapeHtml($data, $allowedTags);
        libxml_use_internal_errors($internalErrors);

        return $res;
    }

    /**
     * @return bool
     */
    public function isAttachmentEnabled()
    {
        return $this->getConfig()->getFrontendIsActiveAttachment();
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    public function getUserSignatureHTML($message): string
    {
        if ($message->getUserId()) {
            $user = $this->getUser($message);

            if($user->getSignature()) {
                $variables  = [];
                /** @var \Mirasvit\Helpdesk\Model\Ticket $ticket */
                $ticket     = $message->getTicket();
                /** @var \Magento\Store\Model\Store $store */
                $store      = $ticket->getStore();

                $variables = array_merge(
                    $variables,
                    [
                        'ticket'     => $ticket,
                        'customer'   => $ticket->getCustomer(),
                        'user'       => $user,
                        'department' => $ticket->getDepartment(),
                        'store'      => $store,
                    ]
                );
                $user->setSignature($this->helpdeskNotification->processVariable($user->getSignature(), $variables, $store->getId()));

                return '<br><br>' . $message->stripTagWithContent($user->getSignature(), ['style']);
            }
        }

        return '';
    }

    public function getRateHtml(Message $message)
    {
        $block = $this->getChildBlock('satisfaction');

        if ($block) {
            /** @var View\Message\Rate $block */
            $block->setItem($message);

            return $block->toHtml();
        } else {
            return '';
        }
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Message $message
     *
     * @return \Magento\User\Model\User
     */
    private function getUser($message)
    {
        $userId = $message->getUserId();
        $key = $userId ?? '';
        if (empty($this->users[$key])) {
            /** @var \Magento\User\Model\User $user */
            $user = $message->getUser();
            $this->users[$key] = $user;
        }

        return $this->users[$key];
    }

    /**
     * @return string
     */
    public function getAttachmentSize()
    {
        return (string)ini_get('upload_max_filesize');
    }

    /**
     * @return bool
     */
    public function getIsTicketLocked()
    {
        /** @var \Mirasvit\Helpdesk\Model\Status $status */
        $status = $this->getTicket()->getStatus();

        return in_array($status->getId(), $this->config->getGeneralLockedStatusList());
    }

    /**
     * @return void
     */
    public function addLockedTicketMessage()
    {
        $this->messageManager->addWarning(__('You are not allowed to add the message to the current ticket as it is locked. To contact support team you should create a new ticket.'));
    }


}
