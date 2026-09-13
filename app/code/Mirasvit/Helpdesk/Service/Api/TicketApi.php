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



namespace Mirasvit\Helpdesk\Service\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Mirasvit\Helpdesk\Api\Service\TicketApiInterface;
use Mirasvit\Helpdesk\Api\Data\MessageSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Data\MessageSearchResultsInterfaceFactory;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Api\Data\TicketCreateDataInterface;
use Mirasvit\Helpdesk\Api\Data\TicketGetResponseInterface;
use Mirasvit\Helpdesk\Api\Data\TicketSearchResultsInterface;
use Mirasvit\Helpdesk\Api\Repository\TicketRepositoryInterface;
use Mirasvit\Helpdesk\Api\Data\TicketGetResponseInterfaceFactory;
use Mirasvit\Helpdesk\Api\Data\MessageItemInterfaceFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Attachment\CollectionFactory as AttachmentCollectionFactory;
use Mirasvit\Helpdesk\Api\Data\AttachmentMetadataInterfaceFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory as FieldCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory as DepartmentCollectionFactory;
use Mirasvit\Helpdesk\Api\Data\CustomFieldItemInterfaceFactory;
use Mirasvit\Helpdesk\Helper\Process;
use Mirasvit\Helpdesk\Helper\Tag as TagHelper;
use Mirasvit\Helpdesk\Helper\Draft as DraftHelper;
use Mirasvit\Helpdesk\Model\Config;

class TicketApi implements TicketApiInterface
{
    private $helpdeskProcess;

    private $storeManager;

    private $userContext;

    private UserFactory $userFactory;

    private UserCollectionFactory $userCollectionFactory;

    private $ticketRepository;

    private $tagHelper;

    private $responseFactory;

    private $messageItemFactory;

    private $messageCollectionFactory;

    private $messageSearchResultsFactory;

    private $attachmentCollectionFactory;

    private $attachmentMetadataFactory;

    private $draftHelper;

    private $fieldCollectionFactory;

    private $customFieldItemFactory;

    private $departmentCollectionFactory;

    private $config;

    public function __construct(
        Process $helpdeskProcess,
        StoreManagerInterface $storeManager,
        UserContextInterface $userContext,
        UserFactory $userFactory,
        UserCollectionFactory $userCollectionFactory,
        TicketRepositoryInterface $ticketRepository,
        TagHelper $tagHelper,
        TicketGetResponseInterfaceFactory $responseFactory,
        MessageItemInterfaceFactory $messageItemFactory,
        MessageCollectionFactory $messageCollectionFactory,
        MessageSearchResultsInterfaceFactory $messageSearchResultsFactory,
        AttachmentCollectionFactory $attachmentCollectionFactory,
        AttachmentMetadataInterfaceFactory $attachmentMetadataFactory,
        DraftHelper $draftHelper,
        FieldCollectionFactory $fieldCollectionFactory,
        CustomFieldItemInterfaceFactory $customFieldItemFactory,
        DepartmentCollectionFactory $departmentCollectionFactory,
        Config $config
    ) {
        $this->helpdeskProcess             = $helpdeskProcess;
        $this->storeManager                = $storeManager;
        $this->userContext                 = $userContext;
        $this->userFactory                 = $userFactory;
        $this->userCollectionFactory       = $userCollectionFactory;
        $this->ticketRepository            = $ticketRepository;
        $this->tagHelper                   = $tagHelper;
        $this->responseFactory             = $responseFactory;
        $this->messageItemFactory          = $messageItemFactory;
        $this->messageCollectionFactory    = $messageCollectionFactory;
        $this->messageSearchResultsFactory = $messageSearchResultsFactory;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        $this->attachmentMetadataFactory   = $attachmentMetadataFactory;
        $this->draftHelper                 = $draftHelper;
        $this->fieldCollectionFactory      = $fieldCollectionFactory;
        $this->customFieldItemFactory      = $customFieldItemFactory;
        $this->departmentCollectionFactory = $departmentCollectionFactory;
        $this->config                      = $config;
    }

    public function create(TicketCreateDataInterface $ticketData): TicketInterface
    {
        $customerEmail = $ticketData->getCustomerEmail();
        $cc            = $ticketData->getCc();
        $bcc           = $ticketData->getBcc();
        $tags          = $ticketData->getTags();

        $this->validateEmail($customerEmail);

        if ($cc !== null) {
            $this->validateCcEmails($cc);
        }

        if ($bcc !== null) {
            $this->validateCcEmails($bcc, 'BCC');
        }

        $user = $this->resolveAdminUser();

        $data = [
            'customer_email' => trim($customerEmail),
            'subject'        => $ticketData->getSubject(),
            'reply'          => $ticketData->getMessage(),
            'reply_type'     => Config::MESSAGE_PUBLIC,
            'store_id'       => $ticketData->getStoreId() ?? $this->storeManager->getStore()->getId(),
            'tags'           => $tags ? implode(',', $tags) : '',
        ];

        $optionalFields = [
            'priority_id'   => $ticketData->getPriorityId(),
            'department_id' => $ticketData->getDepartmentId() ?? $this->resolveDefaultDepartmentId(),
            'status_id'     => $ticketData->getStatusId(),
            'order_id'      => $ticketData->getOrderId(),
            'customer_id'   => $ticketData->getCustomerId(),
            'customer_name' => $ticketData->getCustomerName(),
            'owner'         => $ticketData->getOwner(),
            'cc'            => $cc,
            'bcc'           => $bcc,
        ];

        foreach ($optionalFields as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $this->helpdeskProcess->createOrUpdateFromBackendPost($data, $user);
    }

    public function update(
        string $code,
        ?int $statusId = null,
        ?int $priorityId = null,
        ?int $userId = null,
        ?string $subject = null,
        ?array $tags = null,
        ?int $folder = null,
        ?array $customFields = null
    ): TicketInterface {
        $ticket = $this->ticketRepository->getByCode($code);

        if ($statusId !== null) {
            $ticket->setStatusId($statusId);
        }

        if ($priorityId !== null) {
            $ticket->setPriorityId($priorityId);
        }

        if ($userId !== null) {
            $ticket->setUserId($userId);
        }

        if ($subject !== null) {
            $ticket->setSubject($subject);
        }

        if ($tags !== null) {
            $this->tagHelper->setTags($ticket, $tags);
        }

        if ($folder !== null) {
            $this->validateFolder($folder);
            $ticket->setFolder($folder);
        }

        if ($customFields !== null) {
            $this->applyCustomFields($ticket, $customFields);
        }

        return $this->ticketRepository->save($ticket);
    }

    /**
     * Apply a code => value map to the ticket's custom field columns.
     *
     * Field codes are validated against active mst_helpdesk_field rows so a
     * caller can't write to arbitrary ticket columns (e.g. status_id, code).
     * Unknown codes raise LocalizedException listing what's allowed.
     *
     * @param TicketInterface $ticket
     * @param array $customFields
     * @return void
     * @throws LocalizedException
     */
    private function applyCustomFields(TicketInterface $ticket, array $customFields): void
    {
        if (empty($customFields)) {
            return;
        }

        /** @var \Mirasvit\Helpdesk\Model\Ticket $ticket */

        $allowed = [];
        $fieldCollection = $this->fieldCollectionFactory->create()
            ->addFieldToFilter('is_active', 1);
        foreach ($fieldCollection as $field) {
            $allowed[$field->getCode()] = true;
        }

        foreach ($customFields as $fieldCode => $value) {
            if (!is_string($fieldCode) || $fieldCode === '') {
                throw new LocalizedException(
                    __('Custom field code must be a non-empty string.')
                );
            }
            if (!isset($allowed[$fieldCode])) {
                throw new LocalizedException(
                    __(
                        'Unknown custom field "%1". Allowed codes: %2',
                        $fieldCode,
                        implode(', ', array_keys($allowed)) ?: '(none configured)'
                    )
                );
            }
            $ticket->setData($fieldCode, $value);
        }
    }

    public function delete(string $code): bool
    {
        $ticket = $this->ticketRepository->getByCode($code);

        return $this->ticketRepository->delete($ticket);
    }

    public function get(string $code): TicketGetResponseInterface
    {
        $ticket = $this->ticketRepository->getByCode($code);

        $messageCollection = $this->messageCollectionFactory->create()
            ->addFieldToFilter('ticket_id', $ticket->getId())
            ->setOrder('created_at', 'ASC');

        $messageIds = [];
        foreach ($messageCollection as $message) {
            $messageIds[] = $message->getId();
        }

        $attachmentsByMessage = $this->loadAttachmentMetadata($messageIds);

        $messages = [];
        foreach ($messageCollection as $message) {
            $data = $message->getData();
            $data['attachments'] = $attachmentsByMessage[(int)$message->getId()] ?? [];
            $messages[] = $this->messageItemFactory->create(['data' => $data]);
        }

        $customFields = [];
        $fieldCollection = $this->fieldCollectionFactory->create()
            ->addFieldToFilter('is_active', 1)
            ->setOrder('sort_order', 'ASC');

        foreach ($fieldCollection as $field) {
            $customFields[] = $this->customFieldItemFactory->create(['data' => [
                'field_code'  => $field->getCode(),
                'name'        => $field->getName(),
                'field_value' => $ticket->getData($field->getCode()),
            ]]);
        }

        $response = $this->responseFactory->create();
        $response->setTicket($ticket);
        $response->setMessages($messages);
        $response->setCustomFields($customFields);

        return $response;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): TicketSearchResultsInterface
    {
        return $this->ticketRepository->getList($searchCriteria);
    }

    public function addMessage(
        string $code,
        string $message,
        ?string $type = null,
        ?string $user = null
    ): TicketInterface {
        $message = trim($message);
        if ($message === '') {
            throw new LocalizedException(__('Message body cannot be empty.'));
        }

        $messageType = $this->resolveMessageType($type);

        // resolveAdminUser() authorizes the caller and yields the agent an admin token already
        // identifies; an integration token identifies none, which is what $user supplies.
        $actor = $this->resolveAdminUser();
        $actor = $this->resolveActingAgent($actor, $user);

        // An internal note is agent-authored by definition, so it cannot be recorded without one.
        if ($actor === null && $messageType === Config::MESSAGE_INTERNAL) {
            throw new LocalizedException(
                __('Internal notes are agent-authored: name the agent in the "user" field (agent id or email).')
            );
        }

        $ticket = $this->ticketRepository->getByCode($code);

        if ($actor !== null) {
            $ticket->addMessage(
                $message,
                false,
                $actor,
                Config::USER,
                $messageType,
                false,
                false
            );

            return $ticket;
        }

        // No agent identity: record the reply as the ticket's customer, the same way the storefront does.
        $ticket->addMessage(
            $message,
            $this->helpdeskProcess->getCustomerMessageAuthor($ticket),
            false,
            Config::CUSTOMER,
            $messageType,
            false,
            false
        );

        return $ticket;
    }

    public function getMessages(
        string $code,
        SearchCriteriaInterface $searchCriteria
    ): MessageSearchResultsInterface {
        $ticket = $this->ticketRepository->getByCode($code);

        $collection = $this->messageCollectionFactory->create()
            ->addFieldToFilter('ticket_id', $ticket->getId());

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $fields     = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() ?? [] as $filter) {
                $condition    = $filter->getConditionType() ?: 'eq';
                $fields[]     = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $direction = ($sortOrder->getDirection() === \Magento\Framework\Api\SortOrder::SORT_DESC)
                    ? 'DESC'
                    : 'ASC';
                $collection->setOrder($sortOrder->getField(), $direction);
            }
        } else {
            $collection->setOrder('created_at', 'ASC');
        }

        $totalCount = $collection->getSize();

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $messageIds = [];
        foreach ($collection as $message) {
            $messageIds[] = $message->getId();
        }

        $attachmentsByMessage = $this->loadAttachmentMetadata($messageIds);

        $items = [];
        foreach ($collection as $message) {
            $data = $message->getData();
            $data['attachments'] = $attachmentsByMessage[(int)$message->getId()] ?? [];
            $items[] = $this->messageItemFactory->create(['data' => $data]);
        }

        $searchResults = $this->messageSearchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($items);
        $searchResults->setTotalCount($totalCount);

        return $searchResults;
    }

    private function validateFolder(int $folder): void
    {
        $allowed = [
            Config::FOLDER_INBOX,
            Config::FOLDER_ARCHIVE,
            Config::FOLDER_SPAM,
        ];

        if (!in_array($folder, $allowed, true)) {
            throw new LocalizedException(
                __('Invalid folder "%1". Allowed: 1 (Inbox), 2 (Archive), 3 (Spam)', $folder)
            );
        }
    }

    /**
     * The department a created ticket belongs to when the caller did not name one.
     *
     * Every other creation entry point resolves a department: the storefront form falls back to
     * helpdesk/contact_form/default_department, the mail gateway to the gateway's department or the
     * first active one, the admin form to the posted owner. The API had no fallback, so an API ticket
     * was saved with department_id = NULL and the notification for its first message died on
     * Notification::mail(), which reads the sender name and address off the department. An admin-token
     * call only escaped that by accident - Ticket::addMessage() back-fills the department from the
     * acting agent, which an integration token has none of.
     *
     * @return int|null the configured default when it is still active, else the first active
     *                  department, or null when the installation has no active department at all
     */
    private function resolveDefaultDepartmentId(): ?int
    {
        $configured = (int)$this->config->getContactFormDefaultDepartment();

        $activeIds = array_map(
            'intval',
            $this->departmentCollectionFactory->create()
                ->addFieldToFilter('is_active', true)
                ->getAllIds()
        );

        if ($configured && in_array($configured, $activeIds, true)) {
            return $configured;
        }

        return $activeIds ? reset($activeIds) : null;
    }

    private function validateEmail(string $email): void
    {
        $validator = new \Magento\Framework\Validator\EmailAddress();
        if (!$validator->isValid(trim($email))) {
            throw new LocalizedException(__('Invalid customer email format.'));
        }
    }

    private function validateCcEmails(string $emails, string $fieldName = 'CC'): void
    {
        $validator = new \Magento\Framework\Validator\EmailAddress();
        foreach (explode(',', trim($emails)) as $email) {
            $email = trim($email);
            if ($email && !$validator->isValid($email)) {
                throw new LocalizedException(__('Invalid %1 email format: %2', $fieldName, $email));
            }
        }
    }

    /**
     * @param int[] $messageIds
     * @return array<int, \Mirasvit\Helpdesk\Api\Data\AttachmentMetadataInterface[]>
     */
    private function loadAttachmentMetadata(array $messageIds): array
    {
        if (empty($messageIds)) {
            return [];
        }

        $collection = $this->attachmentCollectionFactory->create()
            ->addFieldToFilter('message_id', ['in' => $messageIds]);

        $result = [];
        foreach ($collection as $attachment) {
            $messageId = (int)$attachment->getData('message_id');
            $result[$messageId][] = $this->attachmentMetadataFactory->create(['data' => [
                'attachment_id' => $attachment->getData('attachment_id'),
                'name'          => $attachment->getData('name'),
                'type'          => $attachment->getData('type'),
                'size'          => $attachment->getData('size'),
                'url'           => $attachment->getUrl(),
            ]]);
        }

        return $result;
    }

    public function saveDraft(string $code, string $body): bool
    {
        $ticket = $this->ticketRepository->getByCode($code);

        $user = $this->resolveAdminUser();

        $this->draftHelper->getCurrentDraft($ticket->getId(), $user ? (int)$user->getId() : null, $body);

        return true;
    }

    public function getDraft(string $code): string
    {
        $ticket = $this->ticketRepository->getByCode($code);

        $draft = $this->draftHelper->getSavedDraft($ticket->getId());

        return $draft ? (string) $draft->getBody() : '';
    }

    public function deleteDraft(string $code): bool
    {
        $ticket = $this->ticketRepository->getByCode($code);

        $this->draftHelper->clearDraft($ticket);

        return true;
    }

    /**
     * Resolve the admin user acting on the current API request.
     *
     * Returns null when the caller authenticated with a Magento Integration OAuth token. An integration is
     * a valid API identity that has no admin_user row, and UserContextInterface::getUserId() returns its
     * integration.integration_id — a different id space that happens to overlap admin_user.user_id, so
     * loading it through UserFactory would silently resolve to an unrelated agent. Callers must treat null
     * as "no agent identity" rather than substituting an id.
     *
     * @return \Magento\User\Model\User|null
     * @throws LocalizedException
     */
    private function resolveAdminUser(): ?User
    {
        $userType = $this->userContext->getUserType();

        if ($userType === UserContextInterface::USER_TYPE_INTEGRATION) {
            return null;
        }

        if ($userType !== UserContextInterface::USER_TYPE_ADMIN) {
            throw new LocalizedException(__('Admin or integration authorization is required.'));
        }

        $user = $this->userFactory->create()->load($this->userContext->getUserId());
        if (!$user->getId()) {
            throw new LocalizedException(__('Admin authorization is required.'));
        }

        return $user;
    }

    /**
     * Apply the agent the request named, if it named one.
     *
     * An integration token carries no agent identity of its own, so the caller has to say who is
     * writing — by `admin_user.user_id` or by email. The agent must exist and be active: a request
     * that names an unknown one fails rather than falling back to an identity nobody asked for,
     * which is the whole point of naming it.
     *
     * An admin token already identifies its author, so naming a different agent there would be
     * impersonation. That is rejected outright rather than silently ignored.
     *
     * @param User|null   $actor the identity the caller's own token resolved to
     * @param string|null $requested `user` from the request: an agent id or email, or null
     *
     * @return User|null the agent to record as author, or null when nothing identifies one
     * @throws LocalizedException
     */
    private function resolveActingAgent(?User $actor, ?string $requested): ?User
    {
        $requested = trim((string)$requested);

        if ($requested === '') {
            return $actor;
        }

        if ($actor !== null) {
            throw new LocalizedException(
                __('This request is already authenticated as an agent; the "user" field cannot name another.')
            );
        }

        $agent = $this->loadActiveAgent($requested);

        if ($agent === null) {
            throw new LocalizedException(
                __('No active agent matches "%1". Pass an existing agent id or email.', $requested)
            );
        }

        return $agent;
    }

    /**
     * Load an active admin user by `admin_user.user_id` or by email.
     *
     * Digits are read as an id and anything else as an email. `admin_user.email` carries no unique
     * index — only the check in User::_beforeSave — so an ambiguous address resolves to nothing
     * rather than to whichever row sorted first.
     *
     * @return User|null
     */
    private function loadActiveAgent(string $idOrEmail): ?User
    {
        $users = $this->userCollectionFactory->create()
            ->addFieldToFilter('is_active', 1);

        if (ctype_digit($idOrEmail)) {
            $users->addFieldToFilter('user_id', (int)$idOrEmail);
        } else {
            $users->addFieldToFilter('email', $idOrEmail);
        }

        if ($users->getSize() !== 1) {
            return null;
        }

        /** @var User $agent */
        $agent = $users->getFirstItem();

        return $agent->getId() ? $agent : null;
    }

    private function resolveMessageType(?string $type): string
    {
        if ($type === null) {
            return Config::MESSAGE_PUBLIC;
        }

        $allowed = [
            Config::MESSAGE_PUBLIC,
            Config::MESSAGE_INTERNAL,
        ];

        if (!in_array($type, $allowed, true)) {
            throw new LocalizedException(
                __('Invalid message type "%1". Allowed: %2', $type, implode(', ', $allowed))
            );
        }

        return $type;
    }
}
