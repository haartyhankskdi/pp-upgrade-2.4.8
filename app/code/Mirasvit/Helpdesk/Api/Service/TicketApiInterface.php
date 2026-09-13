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



declare(strict_types=1);

namespace Mirasvit\Helpdesk\Api\Service;

use Mirasvit\Helpdesk\Api\Data\MessageSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\TicketCreateDataInterface;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Api\Data\TicketGetResponseInterface;
use Mirasvit\Helpdesk\Api\Data\TicketSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface TicketApiInterface
{
    /**
     * Create a new helpdesk ticket
     *
     * Accepts either an admin token or a Magento Integration OAuth token; both need the
     * Mirasvit_Helpdesk::helpdesk_ticket ACL resource.
     *
     * With an admin token the ticket is agent-originated: assigned to the acting agent, with its first
     * message recorded as that agent's reply to the customer.
     *
     * With an integration token there is no agent identity, so the ticket is customer-originated: left
     * unassigned unless $ticketData->getOwner() names an agent explicitly, and its first message is
     * recorded as the customer's. This is the path for unattended external services (chatbots, contact
     * widgets) that capture a customer's own request.
     *
     * @param \Mirasvit\Helpdesk\Api\Data\TicketCreateDataInterface $ticketData
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(TicketCreateDataInterface $ticketData): TicketInterface;

    /**
     * Update an existing helpdesk ticket
     *
     * @param string $code Ticket code identifier
     * @param int|null $statusId
     * @param int|null $priorityId
     * @param int|null $userId
     * @param string|null $subject
     * @param string[]|null $tags
     * @param int|null $folder Folder: 1 = Inbox, 2 = Archive, 3 = Spam
     * @param string[]|null $customFields Map of field code => value (e.g. {"f_product_id": "42"}).
     *                                    Each code must match an active row in mst_helpdesk_field.
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update(
        string $code,
        ?int $statusId = null,
        ?int $priorityId = null,
        ?int $userId = null,
        ?string $subject = null,
        ?array $tags = null,
        ?int $folder = null,
        ?array $customFields = null
    ): TicketInterface;

    /**
     * Delete a helpdesk ticket
     *
     * @param string $code Ticket code identifier
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function delete(string $code): bool;

    /**
     * Get ticket data with messages
     *
     * @param string $code Ticket code identifier
     * @return \Mirasvit\Helpdesk\Api\Data\TicketGetResponseInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(string $code): TicketGetResponseInterface;

    /**
     * Get ticket list with filters, sorting, and pagination
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mirasvit\Helpdesk\Api\Data\TicketSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): TicketSearchResultsInterface;

    /**
     * Add a message to an existing helpdesk ticket
     *
     * With an admin token the message is recorded as the acting agent's. An integration token carries no
     * agent identity, so it names the author in $user; without one the message is recorded as the ticket's
     * customer and "internal" is rejected, because an internal note is agent-authored by definition.
     *
     * @param string $code Ticket code identifier
     * @param string $message
     * @param string|null $type Message type: "public" or "internal" (default: "public").
     * @param string|null $user The agent writing the message — `admin_user.user_id` or email. Required for
     *                          "internal" on an integration token, which has no author of its own. The
     *                          agent must exist and be active; an unknown one is an error. Must be omitted
     *                          on an admin token, which already identifies its author.
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addMessage(
        string $code,
        string $message,
        ?string $type = null,
        ?string $user = null
    ): TicketInterface;

    /**
     * Get paginated messages for a ticket
     *
     * @param string $code Ticket code identifier
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Mirasvit\Helpdesk\Api\Data\MessageSearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMessages(
        string $code,
        SearchCriteriaInterface $searchCriteria
    ): MessageSearchResultsInterface;

    /**
     * Save a draft reply for a ticket
     *
     * The draft is visible to admins in the ticket edit page for review before sending.
     *
     * With an admin token the draft is attributed to the acting agent and marks them as editing the ticket.
     * With an integration token it is stored without an author and without claiming the edit lock.
     *
     * @param string $code Ticket code identifier
     * @param string $body Draft message body
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveDraft(string $code, string $body): bool;

    /**
     * Get the current draft for a ticket
     *
     * @param string $code Ticket code identifier
     * @return string Draft body or empty string if no draft exists
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDraft(string $code): string;

    /**
     * Delete the draft for a ticket
     *
     * @param string $code Ticket code identifier
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteDraft(string $code): bool;

}
