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

interface SatisfactionInterface
{
    const TABLE_NAME = 'mst_helpdesk_satisfaction';

    const ID              = 'satisfaction_id';
    const KEY_TICKET_ID   = 'ticket_id';
    const KEY_MESSAGE_ID  = 'message_id';
    const KEY_USER_ID     = 'user_id';
    const KEY_CUSTOMER_ID = 'customer_id';
    const KEY_STORE_ID    = 'store_id';
    const KEY_RATE        = 'rate';
    const KEY_COMMENT     = 'comment';
    const KEY_CREATED_AT  = 'created_at';
    const KEY_UPDATED_AT  = 'updated_at';

    /**
     * @return int
     */
    public function getSatisfactionId();

    /**
     * @param int $satisfactionId
     * @return $this
     */
    public function setSatisfactionId($satisfactionId);

    /**
     * @return int
     */
    public function getTicketId();

    /**
     * @param int $ticketId
     * @return $this
     */
    public function setTicketId($ticketId);

    /**
     * @return int
     */
    public function getMessageId();

    /**
     * @param int $messageId
     * @return $this
     */
    public function setMessageId($messageId);

    /**
     * @return int
     */
    public function getUserId();

    /**
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * @return int
     */
    public function getRate();

    /**
     * @param int $rate
     * @return $this
     */
    public function setRate($rate);

    /**
     * @return string
     */
    public function getComment();

    /**
     * @param string $comment
     * @return $this
     */
    public function setComment($comment);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}
