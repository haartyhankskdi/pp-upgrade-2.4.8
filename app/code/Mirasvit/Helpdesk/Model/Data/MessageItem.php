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
use Mirasvit\Helpdesk\Api\Data\MessageItemInterface;

class MessageItem extends DataObject implements MessageItemInterface
{
    /**
     * @inheritDoc
     */
    public function getMessageId()
    {
        return $this->getData('message_id');
    }

    /**
     * @inheritDoc
     */
    public function getTicketId()
    {
        return $this->getData('ticket_id');
    }

    /**
     * @inheritDoc
     */
    public function getUserId()
    {
        return $this->getData('user_id');
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * @inheritDoc
     */
    public function getCustomerEmail()
    {
        return $this->getData('customer_email');
    }

    /**
     * @inheritDoc
     */
    public function getCustomerName()
    {
        return $this->getData('customer_name');
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->getData('body');
    }

    /**
     * @inheritDoc
     */
    public function getBodyFormat()
    {
        return $this->getData('body_format');
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData('type');
    }

    /**
     * @inheritDoc
     */
    public function getTriggeredBy()
    {
        return $this->getData('triggered_by');
    }

    /**
     * @inheritDoc
     */
    public function getIsRead()
    {
        return $this->getData('is_read');
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }

    /**
     * @inheritDoc
     */
    public function getUserName()
    {
        return $this->getData('user_name');
    }

    /**
     * @inheritDoc
     */
    public function getThirdPartyEmail()
    {
        return $this->getData('third_party_email');
    }

    /**
     * @inheritDoc
     */
    public function getThirdPartyName()
    {
        return $this->getData('third_party_name');
    }

    /**
     * @inheritDoc
     */
    public function getAttachments()
    {
        return $this->getData('attachments') ?: [];
    }
}
