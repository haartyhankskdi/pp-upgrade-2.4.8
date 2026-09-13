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



namespace Mirasvit\Helpdesk\Api\Data;

interface TicketGetResponseInterface
{
    /**
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface
     */
    public function getTicket();

    /**
     * @param \Mirasvit\Helpdesk\Api\Data\TicketInterface $ticket
     * @return $this
     */
    public function setTicket(\Mirasvit\Helpdesk\Api\Data\TicketInterface $ticket);

    /**
     * @return \Mirasvit\Helpdesk\Api\Data\MessageItemInterface[]
     */
    public function getMessages();

    /**
     * @param \Mirasvit\Helpdesk\Api\Data\MessageItemInterface[] $messages
     * @return $this
     */
    public function setMessages(array $messages);

    /**
     * @return \Mirasvit\Helpdesk\Api\Data\CustomFieldItemInterface[]
     */
    public function getCustomFields();

    /**
     * @param \Mirasvit\Helpdesk\Api\Data\CustomFieldItemInterface[] $customFields
     * @return $this
     */
    public function setCustomFields(array $customFields);
}
