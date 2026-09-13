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

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface TicketCreateDataInterface extends ExtensibleDataInterface
{
    const KEY_CUSTOMER_EMAIL = 'customer_email';
    const KEY_SUBJECT        = 'subject';
    const KEY_MESSAGE        = 'message';
    const KEY_STORE_ID       = 'store_id';
    const KEY_PRIORITY_ID    = 'priority_id';
    const KEY_DEPARTMENT_ID  = 'department_id';
    const KEY_STATUS_ID      = 'status_id';
    const KEY_ORDER_ID       = 'order_id';
    const KEY_CUSTOMER_ID    = 'customer_id';
    const KEY_CUSTOMER_NAME  = 'customer_name';
    const KEY_OWNER          = 'owner';
    const KEY_CC             = 'cc';
    const KEY_BCC            = 'bcc';
    const KEY_TAGS           = 'tags';

    /**
     * @return string
     */
    public function getCustomerEmail(): string;

    /**
     * @param string $customerEmail
     * @return $this
     */
    public function setCustomerEmail(string $customerEmail): TicketCreateDataInterface;

    /**
     * @return string
     */
    public function getSubject(): string;

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject): TicketCreateDataInterface;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): TicketCreateDataInterface;

    /**
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * @param int|null $storeId
     * @return $this
     */
    public function setStoreId(?int $storeId): TicketCreateDataInterface;

    /**
     * @return int|null
     */
    public function getPriorityId(): ?int;

    /**
     * @param int|null $priorityId
     * @return $this
     */
    public function setPriorityId(?int $priorityId): TicketCreateDataInterface;

    /**
     * @return int|null
     */
    public function getDepartmentId(): ?int;

    /**
     * @param int|null $departmentId
     * @return $this
     */
    public function setDepartmentId(?int $departmentId): TicketCreateDataInterface;

    /**
     * @return int|null
     */
    public function getStatusId(): ?int;

    /**
     * @param int|null $statusId
     * @return $this
     */
    public function setStatusId(?int $statusId): TicketCreateDataInterface;

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId): TicketCreateDataInterface;

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): TicketCreateDataInterface;

    /**
     * @return string|null
     */
    public function getCustomerName(): ?string;

    /**
     * @param string|null $customerName
     * @return $this
     */
    public function setCustomerName(?string $customerName): TicketCreateDataInterface;

    /**
     * @return string|null
     */
    public function getOwner(): ?string;

    /**
     * @param string|null $owner
     * @return $this
     */
    public function setOwner(?string $owner): TicketCreateDataInterface;

    /**
     * @return string|null
     */
    public function getCc(): ?string;

    /**
     * @param string|null $cc
     * @return $this
     */
    public function setCc(?string $cc): TicketCreateDataInterface;

    /**
     * @return string|null
     */
    public function getBcc(): ?string;

    /**
     * @param string|null $bcc
     * @return $this
     */
    public function setBcc(?string $bcc): TicketCreateDataInterface;

    /**
     * @return string[]|null
     */
    public function getTags(): ?array;

    /**
     * @param string[]|null $tags
     * @return $this
     */
    public function setTags(?array $tags = null): TicketCreateDataInterface;

    /**
     * @return \Mirasvit\Helpdesk\Api\Data\TicketCreateDataExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Mirasvit\Helpdesk\Api\Data\TicketCreateDataExtensionInterface;

    /**
     * @param \Mirasvit\Helpdesk\Api\Data\TicketCreateDataExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mirasvit\Helpdesk\Api\Data\TicketCreateDataExtensionInterface $extensionAttributes
    ): TicketCreateDataInterface;
}
