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


namespace Mirasvit\Helpdesk\Block\Adminhtml;

/**
 * Customer account dropdown link
 */
class ToolbarTickets extends \Magento\Backend\Block\Template
{
    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    private $config;
    /**
     * @var \Mirasvit\Helpdesk\Helper\Permission
     */
    private $helpdeskPermission;
    /**
     * @var \Mirasvit\Helpdesk\Helper\DesktopNotification
     */
    private $helpdeskTicket;
    /**
     * @var \Mirasvit\Helpdesk\Helper\User
     */
    private $helpdeskUser;

    /**
     * ToolbarTickets constructor.
     * @param \Mirasvit\Helpdesk\Helper\DesktopNotification $helpdeskTicket
     * @param \Mirasvit\Helpdesk\Helper\Permission $helpdeskPermission
     * @param \Mirasvit\Helpdesk\Helper\User $helpdeskUser
     * @param \Mirasvit\Helpdesk\Model\Config $config
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Mirasvit\Helpdesk\Helper\DesktopNotification $helpdeskTicket,
        \Mirasvit\Helpdesk\Helper\Permission $helpdeskPermission,
        \Mirasvit\Helpdesk\Helper\User $helpdeskUser,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->config             = $config;
        $this->helpdeskUser       = $helpdeskUser;
        $this->helpdeskPermission = $helpdeskPermission;
        $this->helpdeskTicket     = $helpdeskTicket;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return int
     */
    public function getNotificationInterval()
    {
        return $this->getConfig()->getDesktopNotificationCheckPeriod();
    }

    /**
     * @return string
     */
    public function getCheckNotificationUrl()
    {
        if (!$permission = $this->helpdeskPermission->getPermission()) {
            return '';
        } else {
            return $this->getUrl('helpdesk/ticket/checknotification');
        }
    }

    /**
     * @return int
     */
    public function getNotificationDescriptionLength()
    {
        return 100;
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\Collection
     */
    public function getLatestNewTickets()
    {
        $collection = $this->helpdeskTicket->getNewTickets();
        $collection->getSelect()->limit(50);

        return $collection;
    }

    /**
     * @return int
     */
    public function getNewTickets()
    {
        return $this->helpdeskTicket->getNewTicketsNumber();
    }

    /**
     * @return int
     */
    public function getUserTickets()
    {
        /** @var \Mirasvit\Helpdesk\Model\User $user */
        $user = $this->getUser();

        return $this->helpdeskTicket->getUserMessagesNumber($user);
    }

    /**
     * @return bool|\Mirasvit\Helpdesk\Model\User|\Magento\User\Model\User
     */
    public function getUser()
    {
        return $this->helpdeskUser->getHelpdeskUser();
    }

    private function isAllowed()
    {
        return $this->getAuthorization()->isAllowed('Mirasvit_Helpdesk::helpdesk_ticket');
    }

    public function toHtml()
    {
        return $this->isAllowed() ? parent::toHtml() : '';
    }
}
