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



namespace Mirasvit\Helpdesk\Block\Adminhtml\Ticket\Edit\Tab\General;

use Magento\Framework\View\Element\Template;
use Mirasvit\Helpdesk\Api\Service\Ticket\TicketManagementInterface;
use Mirasvit\Core\Service\SerializeService as Serializer;

class CustomerSummary extends Template
{
    private $customerNoteFactory;
    private $stringUtil;

    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    protected $config;
    /**
     * @var Template\Context
     */
    protected $context;
    /**
     * @var \Mirasvit\Helpdesk\Helper\Customer
     */
    protected $helpdeskCustomer;
    /**
     * @var \Mirasvit\Helpdesk\Helper\Order
     */
    protected $helpdeskOrder;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var TicketManagementInterface
     */
    protected $ticketManagement;
    /**
     * @var \Mirasvit\Helpdesk\Helper\Timezone
     */
    protected $timezoneHelper;

    /**
     * CustomerSummary constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Mirasvit\Helpdesk\Helper\Customer $helpdeskCustomer
     * @param \Mirasvit\Helpdesk\Helper\Order $helpdeskOrder
     * @param TicketManagementInterface $ticketManagement
     * @param \Mirasvit\Helpdesk\Helper\StringUtil $stringUtil
     * @param \Mirasvit\Helpdesk\Helper\Timezone $timezoneHelper
     * @param \Mirasvit\Helpdesk\Model\Config $config
     * @param Template\Context $context
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Mirasvit\Helpdesk\Helper\Customer $helpdeskCustomer,
        \Mirasvit\Helpdesk\Helper\Order $helpdeskOrder,
        TicketManagementInterface $ticketManagement,
        \Mirasvit\Helpdesk\Helper\StringUtil $stringUtil,
        \Mirasvit\Helpdesk\Helper\Timezone $timezoneHelper,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Mirasvit\Helpdesk\Model\CustomerNoteFactory $customerNoteFactory,
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        $this->customerNoteFactory = $customerNoteFactory;
        $this->registry            = $registry;
        $this->helpdeskCustomer    = $helpdeskCustomer;
        $this->helpdeskOrder       = $helpdeskOrder;
        $this->ticketManagement    = $ticketManagement;
        $this->stringUtil          = $stringUtil;
        $this->timezoneHelper      = $timezoneHelper;
        $this->config              = $config;
        $this->context             = $context;

        parent::__construct($context, []);
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Ticket
     */
    public function getTicket()
    {
        return $this->registry->registry('current_ticket');
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getConfigJson()
    {
        $ordersUrl = '';

        $ticket = $this->getTicket();

        $ordersOptions    = [
            [
                'name' => (string)__('Loading...'),
                'id'   => 0,
            ],
        ];

        $hasOrders = false;
        if ($ticket->getCustomerId() || $ticket->getQuoteAddressId()) {
            $customers = $this->helpdeskCustomer->getCustomerArray(
                false,
                $ticket->getCustomerId(),
                $ticket->getQuoteAddressId()
            );
            $email = false;
            foreach ($customers as $value) {
                $email = $value['email'];
            }

            $hasOrders = $this->helpdeskOrder->hasOrders($email, $ticket->getCustomerId());
            $params = [
                'customer_id' => $ticket->getCustomerId(),
                'email'       => $email,
            ];
            $ordersUrl = $this->context->getUrlBuilder()->getUrl('helpdesk/ticket/loadOrders/', $params);
        } elseif ($ticket->getCustomerEmail()) {
            $hasOrders = $this->helpdeskOrder->hasOrders($ticket->getCustomerEmail());
            $params = [
                'customer_id' => '',
                'email'       => $ticket->getCustomerEmail(),
            ];
            $ordersUrl = $this->context->getUrlBuilder()->getUrl('helpdesk/ticket/loadOrders/', $params);
        }

        $url = '#';
        if ($ticket->getCustomerId()) {
            $url = $this->context->getUrlBuilder()->getUrl('customer/index/edit/', ['id' => $ticket->getCustomerId()]);
        }
        $config = [
            '_customer'        => [
                'id'        => $ticket->getCustomerId(),
                'email'     => $ticket->getCustomerEmail(),
                'cc'        => $ticket->getCc() ? implode(', ', $ticket->getCc()) : '',
                'bcc'       => $ticket->getBcc() ? implode(', ', $ticket->getBcc()) : '',
                'name'      => $ticket->getCustomerName(),
                'url'       => $url,
                'orders'    => $ordersOptions,
                'hasOrders' => $hasOrders,
            ],
            '_orderId'         => (int)$ticket->getOrderId(),
            '_rmas'            => $this->ticketManagement->getRmasOptions($ticket),
            '_emailTo'         => $ticket->getCustomerEmail(),
            '_loaderImg'       => $this->getViewFileUrl('images/loader-2.gif'),
            '_autocompleteUrl' => $this->getUrl('helpdesk/ticket/customerfind'),

            '_localTime'    => $this->getLocalTime(),
            '_localIsNight' => $this->isLocalNight(),
            '_nightImg'     => $this->getViewFileUrl('Mirasvit_Helpdesk::images/night.svg'),
            '_dayImg'       => $this->getViewFileUrl('Mirasvit_Helpdesk::images/day.svg'),
            '_ordersUrl'    => $ordersUrl,

            '_customerNote' => $this->stringUtil->notesHtmlEscapeAndLinkUrls($this->getCustomerNote()),
        ];

        return Serializer::encode($config);
    }

    /**
     * @return string
     */
    public function getCustomerNote()
    {
        $ticket   = $this->getTicket();
        $note = $this->customerNoteFactory->create()->load($ticket->getCustomerId());

        return (string)$note->getCustomerNote();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLocalTime()
    {
        $ticket   = $this->getTicket();
        $timezone = $this->timezoneHelper->getCustomerTimezone($ticket->getCustomerId(), $ticket->getCustomerEmail());

        if (!$timezone || !$this->config->getIsShowCustomerTime()) {
            return "";
        }

        $tz   = new \DateTimeZone($timezone);
        $date = new \DateTime("now", $tz);
        $utc  = $date->format("T");

        return (string)__("%1, %2", $this->formatTime(
            $date,
            \IntlDateFormatter::SHORT
        ), $utc);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function isLocalNight()
    {
        $ticket   = $this->getTicket();
        $timezone = $this->timezoneHelper->getCustomerTimezone($ticket->getCustomerId(), $ticket->getCustomerEmail());

        if (!$timezone) {
            return false;
        }

        return $this->timezoneHelper->isLocalNight($timezone);
    }
}
