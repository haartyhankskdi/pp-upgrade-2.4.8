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



namespace Mirasvit\Helpdesk\Model\Data;

use Magento\Framework\DataObject;
use Mirasvit\Helpdesk\Api\Data\TicketGetResponseInterface;

class TicketGetResponse extends DataObject implements TicketGetResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getTicket()
    {
        return $this->getData('ticket');
    }

    /**
     * @inheritDoc
     */
    public function setTicket(\Mirasvit\Helpdesk\Api\Data\TicketInterface $ticket)
    {
        return $this->setData('ticket', $ticket);
    }

    /**
     * @inheritDoc
     */
    public function getMessages()
    {
        return $this->getData('messages') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setMessages(array $messages)
    {
        return $this->setData('messages', $messages);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFields()
    {
        return $this->getData('custom_fields') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setCustomFields(array $customFields)
    {
        return $this->setData('custom_fields', $customFields);
    }
}
