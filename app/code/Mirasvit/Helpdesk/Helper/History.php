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

use Mirasvit\Helpdesk\Api\Data\FieldInterface;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Model\Config as Config;

class History extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $loggedFields = [
        TicketInterface::KEY_SUBJECT,
        TicketInterface::KEY_FOLDER,
        TicketInterface::KEY_STATUS_ID,
        TicketInterface::KEY_ORDER_ID,
        TicketInterface::KEY_PRIORITY_ID,
        TicketInterface::KEY_DEPARTMENT_ID,
        TicketInterface::KEY_CUSTOMER_ID,
        TicketInterface::KEY_USER_ID,
        TicketInterface::KEY_CUSTOMER_EMAIL,
        TicketInterface::KEY_CUSTOMER_NAME,
        TicketInterface::KEY_CHANNEL,
        TicketInterface::KEY_CC,
        TicketInterface::KEY_BCC,
    ];

    protected $context;

    protected $customerFactory;

    protected $departmentFactory;

    protected $fieldFactory;

    protected $historyFactory;

    protected $orderFactory;

    protected $priorityFactory;

    protected $statusFactory;

    protected $ticketFactory;

    protected $userFactory;

    public function __construct(
        \Mirasvit\Helpdesk\Model\DepartmentFactory $departmentFactory,
        \Mirasvit\Helpdesk\Model\FieldFactory $fieldFactory,
        \Mirasvit\Helpdesk\Model\HistoryFactory $historyFactory,
        \Mirasvit\Helpdesk\Model\PriorityFactory $priorityFactory,
        \Mirasvit\Helpdesk\Model\StatusFactory $statusFactory,
        \Mirasvit\Helpdesk\Model\TicketFactory $ticketFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->customerFactory   = $customerFactory;
        $this->departmentFactory = $departmentFactory;
        $this->fieldFactory      = $fieldFactory;
        $this->historyFactory    = $historyFactory;
        $this->orderFactory      = $orderFactory;
        $this->priorityFactory   = $priorityFactory;
        $this->statusFactory     = $statusFactory;
        $this->ticketFactory     = $ticketFactory;
        $this->userFactory       = $userFactory;
        $this->context           = $context;

        parent::__construct($context);
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @param string                          $triggeredBy - type, like Config::USER, Config::RULE
     * @param array                           $by - array of objects
     *
     * @return \Mirasvit\Helpdesk\Model\History
     */
    public function getHistoryRecord($ticket, $triggeredBy, $by)
    {
        $history = $this->historyFactory->create();
        $history->setTicketId($ticket->getId());
        $history->setTriggeredBy($triggeredBy);
        switch ($triggeredBy) {
            case Config::CUSTOMER:
                $history->setName($by['customer']->getName());
                break;
            case Config::USER:
                if (!empty($by['user'])) {
                    $history->setName($by['user']->getName());
                }
                break;
            case Config::THIRD:
                if (!empty($by['email'])) {
                    $history->setName($by['email']->getSenderNameOrEmail());
                } else {
                    $history->setName(__('Unable to define email sender'));
                }
                break;
            case Config::RULE:
                $history->setName(__("Rule '%1'", $by['rule']->getName()));
                break;
            case Config::FOLLOWUP:
                $history->setName(__("Follow Up actions"));
                break;
        }

        return $history;
    }

    /**
     * Returns a text like 'Status: In Progress => Closed'
     *
     * @param \Magento\Framework\DataObject $stateBefore
     * @param \Magento\Framework\DataObject $stateAfter
     * @param string                        $field
     * @param string                        $fieldLabel
     *
     * @return bool|\Magento\Framework\Phrase
     */
    protected function getText($stateBefore, $stateAfter, $field, $fieldLabel)
    {
        $text = false;
        if ($stateBefore->getData($field) != $stateAfter->getData($field)) {
            $fieldFactory = $this->getFieldFactory($field);
            if ($stateBefore->getData($field)) {
                if ($fieldFactory) {
                    $oldStatus = $fieldFactory->create()->load($stateBefore->getData($field));
                    $newStatus = $fieldFactory->create()->load($stateAfter->getData($field));
                    if ($field == TicketInterface::KEY_ORDER_ID) {
                        $text = __(
                            '%1: %2 => %3', $fieldLabel, $oldStatus->getIncrementId(), $newStatus->getIncrementId()
                        );
                    } else {
                        $text = __(
                            '%1: %2 => %3', $fieldLabel, $oldStatus->getName(), $newStatus->getName()
                        );
                    }
                } elseif ($field == TicketInterface::KEY_FOLDER) {
                    $text = __(
                        '%1: %2 => %3', $fieldLabel, $stateBefore->getFolderName(), $stateAfter->getFolderName()
                    );
                } else {
                    $text = __(
                        '%1: %2 => %3', $fieldLabel, $stateBefore->getData($field), $stateAfter->getData($field)
                    );
                }
            } else {
                if ($fieldFactory) {
                    if ($field == TicketInterface::KEY_ORDER_ID) {
                        $newStatus = $fieldFactory->create()->load($stateAfter->getData($field));
                        $text      = __('%1: %2', $fieldLabel, $newStatus->getIncrementId());
                    } else {
                        $newStatus = $fieldFactory->create()->load($stateAfter->getData($field));
                        $text      = __('%1: %2', $fieldLabel, $newStatus->getName());
                    }
                } elseif ($field == TicketInterface::KEY_FOLDER) {
                    $text = __('%1: %2', $fieldLabel, $stateAfter->getFolderName());
                } else {
                    $text = __('%1: %2', $fieldLabel, $stateAfter->getData($field));
                }
            }
        }

        return $text;
    }

    /**
     * Returns a text like 'Status: In Progress => Closed'
     *
     * @param \Magento\Framework\DataObject $stateBefore
     * @param \Magento\Framework\DataObject $stateAfter
     * @param string                        $field
     *
     * @return bool|\Magento\Framework\Phrase
     */
    protected function getCustomFieldText($stateBefore, $stateAfter, $field)
    {
        $text = false;
        if ($stateBefore->getData($field) != $stateAfter->getData($field)) {
            $fieldModel = $this->fieldFactory->create()->load(
                $field, FieldInterface::KEY_CODE
            );
            if ($stateBefore->getData($field)) {
                $text = __(
                    '%1: %2 => %3', $fieldModel->getName(), $stateBefore->getData($field), $stateAfter->getData($field)
                );
            } else {
                $text = __('%1: %2', $fieldModel->getName(), $stateAfter->getData($field));
            }
        }

        return $text;
    }

    /**
     * We call this functions after changes of the ticket
     *
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @param \Magento\Framework\DataObject   $stateBefore
     * @param \Magento\Framework\DataObject   $stateAfter
     * @param string                          $triggeredBy
     * @param array                           $by
     *
     * @return void
     */
    public function changeTicket($ticket, $stateBefore, $stateAfter, $triggeredBy, $by)
    {
        if (!$ticket->getId()) { //new ticket
            return;
        }

        $changedFields = array_diff_assoc(
            $this->prepareDataToComparison($stateAfter->getData()),
            $this->prepareDataToComparison($stateBefore->getData())
        );

        $history = $this->getHistoryRecord($ticket, $triggeredBy, $by);
        $text    = [];
        foreach ($changedFields as $field => $v) {
            if (in_array($field, $this->loggedFields)) {
                $text[] = $this->getText($stateBefore, $stateAfter, $field, $this->getFieldLabel($field));
            } elseif (strpos($field, 'f_') === 0) {
                $text[] = $this->getCustomFieldText($stateBefore, $stateAfter, $field);
            }
        }

        $text = array_diff($text, [false]);//remove empty values

        if ($ticket->getMergedTicketId()) {
            $newTicket = $this->ticketFactory->create()->load($ticket->getMergedTicketId());
            $text[]    = __('Ticket was merged to: %1', $newTicket->getCode());
        }
        if (isset($by['codes'])) {
            $text[] = __('Ticket was merged with: %1', implode(', ', $by['codes']));
        }
        $history->addMessage($text);
    }

    /**
     * We call this functions after adding messages to the ticket
     *
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @param string                          $triggeredBy
     * @param array                           $by
     * @param string                          $messageType
     *
     * @return void
     */
    public function addMessage($ticket, $triggeredBy, $by, $messageType)
    {
        $history = $this->getHistoryRecord($ticket, $triggeredBy, $by);
        $text    = [];
        switch ($messageType) {
            case Config::MESSAGE_PUBLIC:
                $text[] = __('Message added');
                break;
            case Config::MESSAGE_INTERNAL:
                $text[] = __('Internal note added');
                break;
            case Config::MESSAGE_PUBLIC_THIRD:
                $text[] = __('Third party message added');
                break;
            case Config::MESSAGE_INTERNAL_THIRD:
                $text[] = __('Private third party message added');
                break;
        }
        $history->addMessage($text);
    }

    /**
     * @param string $fieldId
     *
     * @return string
     */
    private function getFieldLabel($fieldId)
    {
        $labels = [
            TicketInterface::KEY_SUBJECT        => __('Subject'),
            TicketInterface::KEY_FOLDER         => __('Folder'),
            TicketInterface::KEY_STATUS_ID      => __('Status'),
            TicketInterface::KEY_ORDER_ID       => __('Order'),
            TicketInterface::KEY_PRIORITY_ID    => __('Priority'),
            TicketInterface::KEY_DEPARTMENT_ID  => __('Department'),
            TicketInterface::KEY_USER_ID        => __('User'),
            TicketInterface::KEY_CUSTOMER_ID    => __('Customer'),
            TicketInterface::KEY_CUSTOMER_EMAIL => __('Customer Email'),
            TicketInterface::KEY_CUSTOMER_NAME  => __('Customer Name'),
            TicketInterface::KEY_CHANNEL        => __('Channel'),
            TicketInterface::KEY_CC             => __('Cc'),
            TicketInterface::KEY_BCC            => __('Bcc'),
        ];

        return isset($labels[$fieldId]) ? $labels[$fieldId] : '';
    }

    /**
     * @param string $fieldId
     *
     * @return object
     */
    private function getFieldFactory($fieldId)
    {
        $labels = [
            TicketInterface::KEY_STATUS_ID     => $this->statusFactory,
            TicketInterface::KEY_ORDER_ID      => $this->orderFactory,
            TicketInterface::KEY_PRIORITY_ID   => $this->priorityFactory,
            TicketInterface::KEY_DEPARTMENT_ID => $this->departmentFactory,
            TicketInterface::KEY_CUSTOMER_ID   => $this->customerFactory,
            TicketInterface::KEY_USER_ID       => $this->userFactory,
        ];

        return isset($labels[$fieldId]) ? $labels[$fieldId] : null;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function prepareDataToComparison($data)
    {
        $result = [];
        foreach ($data as $field => $v) {
            if (in_array($field, $this->loggedFields) || strpos($field, 'f_') === 0) {
                $result[$field] = $data[$field];
            }
        }

        return $result;
    }
}
