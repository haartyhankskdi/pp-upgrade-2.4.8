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

interface HistoryInterface
{
    const TABLE_NAME = 'mst_helpdesk_history';

    const ID               = 'history_id';
    const KEY_TICKET_ID    = 'ticket_id';
    const KEY_TRIGGERED_BY = 'triggered_by';
    const KEY_NAME         = 'name';
    const KEY_MESSAGE      = 'message';
    const KEY_CREATED_AT   = 'created_at';

    /**
     * @return int
     */
    public function getHistoryId();

    /**
     * @param int $historyId
     * @return $this
     */
    public function setHistoryId($historyId);

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
     * @return string
     */
    public function getTriggeredBy();

    /**
     * @param string $triggeredBy
     * @return $this
     */
    public function setTriggeredBy($triggeredBy);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
}
