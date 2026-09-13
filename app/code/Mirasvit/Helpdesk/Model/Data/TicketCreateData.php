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

use Magento\Framework\Api\AbstractExtensibleObject;
use Mirasvit\Helpdesk\Api\Data\TicketCreateDataInterface;

class TicketCreateData extends AbstractExtensibleObject implements TicketCreateDataInterface
{
    /**
     * @inheritDoc
     */
    public function getCustomerEmail(): string
    {
        return (string) $this->_get(self::KEY_CUSTOMER_EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerEmail(string $customerEmail): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_CUSTOMER_EMAIL, $customerEmail);
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return (string) $this->_get(self::KEY_SUBJECT);
    }

    /**
     * @inheritDoc
     */
    public function setSubject(string $subject): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_SUBJECT, $subject);
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return (string) $this->_get(self::KEY_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setMessage(string $message): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): ?int
    {
        $value = $this->_get(self::KEY_STORE_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function setStoreId(?int $storeId): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getPriorityId(): ?int
    {
        $value = $this->_get(self::KEY_PRIORITY_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function setPriorityId(?int $priorityId): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_PRIORITY_ID, $priorityId);
    }

    /**
     * @inheritDoc
     */
    public function getDepartmentId(): ?int
    {
        $value = $this->_get(self::KEY_DEPARTMENT_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function setDepartmentId(?int $departmentId): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_DEPARTMENT_ID, $departmentId);
    }

    /**
     * @inheritDoc
     */
    public function getStatusId(): ?int
    {
        $value = $this->_get(self::KEY_STATUS_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function setStatusId(?int $statusId): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_STATUS_ID, $statusId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): ?int
    {
        $value = $this->_get(self::KEY_ORDER_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(?int $orderId): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): ?int
    {
        $value = $this->_get(self::KEY_CUSTOMER_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(?int $customerId): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerName(): ?string
    {
        return $this->_get(self::KEY_CUSTOMER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerName(?string $customerName): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_CUSTOMER_NAME, $customerName);
    }

    /**
     * @inheritDoc
     */
    public function getOwner(): ?string
    {
        return $this->_get(self::KEY_OWNER);
    }

    /**
     * @inheritDoc
     */
    public function setOwner(?string $owner): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_OWNER, $owner);
    }

    /**
     * @inheritDoc
     */
    public function getCc(): ?string
    {
        return $this->_get(self::KEY_CC);
    }

    /**
     * @inheritDoc
     */
    public function setCc(?string $cc): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_CC, $cc);
    }

    /**
     * @inheritDoc
     */
    public function getBcc(): ?string
    {
        return $this->_get(self::KEY_BCC);
    }

    /**
     * @inheritDoc
     */
    public function setBcc(?string $bcc): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_BCC, $bcc);
    }

    /**
     * @inheritDoc
     */
    public function getTags(): ?array
    {
        return $this->_get(self::KEY_TAGS);
    }

    /**
     * @inheritDoc
     */
    public function setTags(?array $tags = null): TicketCreateDataInterface
    {
        return $this->setData(self::KEY_TAGS, $tags);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?\Mirasvit\Helpdesk\Api\Data\TicketCreateDataExtensionInterface
    {
        /** @var \Mirasvit\Helpdesk\Api\Data\TicketCreateDataExtensionInterface|null $extensionAttributes */
        $extensionAttributes = $this->_getExtensionAttributes();

        return $extensionAttributes;
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(
        \Mirasvit\Helpdesk\Api\Data\TicketCreateDataExtensionInterface $extensionAttributes
    ): TicketCreateDataInterface {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
