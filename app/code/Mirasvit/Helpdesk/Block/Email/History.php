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



namespace Mirasvit\Helpdesk\Block\Email;

use Mirasvit\Helpdesk\Model\TicketFactory;
use Mirasvit\Helpdesk\Model\Config;
use Mirasvit\Helpdesk\Helper\StringUtil;
use Magento\Framework\View\Element\Template\Context;

/**
 * @method $this setTicket(\Mirasvit\Helpdesk\Model\Ticket $ticket)
 * @method \Mirasvit\Helpdesk\Model\Ticket getTicket()
 */
class History extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Mirasvit\Helpdesk\Model\TicketFactory
     */
    private $ticketFactory;
    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $context;
    /**
     * @var \Mirasvit\Helpdesk\Helper\StringUtil
     */
    private $utils;

    /**
     * @param \Mirasvit\Helpdesk\Model\TicketFactory           $ticketFactory
     * @param \Mirasvit\Helpdesk\Model\Config                  $config
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mirasvit\Helpdesk\Helper\StringUtil             $utils
     * @param array                                            $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Mirasvit\Helpdesk\Helper\StringUtil $utils,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->ticketFactory  = $ticketFactory;
        $this->config         = $config;
        $this->context        = $context;
        $this->utils          = $utils;

        parent::__construct($context, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setData('area', 'frontend');
    }

    /**
     * @return \Mirasvit\Helpdesk\Helper\StringUtil
     */
    public function getHelpdeskString()
    {
        return $this->utils;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return (int)$this->config->getNotificationHistoryRecordsNumber();
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Message[]|\Mirasvit\Helpdesk\Model\ResourceModel\Message\Collection
     */
    public function getMessages()
    {
        $collection = $this->getTicket()->getMessages();

        if (str_contains((string)$this->getTicket()->getMessages(true)->getFirstItem()->getType(), 'internal')) {
            //show first message for the internal message history
            $collection->getSelect()->limit($this->getLimit()); //show first message
        } else {
            $collection->getSelect()->limit($this->getLimit(), 1); //don't show first message
        }

        return $collection;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Ticket | null
     */
    public function getTicket() {

        if ($this->getData('ticket')) {
            return $this->getData('ticket');
        }

        $ticketId = (int) $this->getRequest()->getParam('id');

        if ($ticketId) {
            return $this->ticketFactory->create()->load($ticketId);
        }

        return null;
    }
}
