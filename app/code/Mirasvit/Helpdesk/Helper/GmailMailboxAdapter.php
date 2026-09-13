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


namespace Mirasvit\Helpdesk\Helper;

use Google\Service\Gmail;

class GmailMailboxAdapter
{
    protected $service;
    protected $userId;

    /** @var GmailMessageAdapter[]|null  all INBOX messages, loaded on demand */
    protected $allMessages = null;

    /** @var GmailMessageAdapter[]|null  UNREAD messages, loaded on demand */
    protected $unreadMessages = null;

    public function __construct(Gmail $service, $userId = 'me')
    {
        $this->service = $service;
        $this->userId  = $userId;
    }

    protected function fetchFromApi(array $params): array
    {
        $messages = [];
        do {
            $response = $this->service->users_messages->listUsersMessages($this->userId, $params);

            if ($response->getMessages()) {
                foreach ($response->getMessages() as $msg) {
                    $gmailMessage = $this->service->users_messages->get($this->userId, $msg->getId());
                    $messages[] = new GmailMessageAdapter($gmailMessage, $this->service, $this->userId);
                }
            }

            $params['pageToken'] = $response->getNextPageToken();
        } while ($params['pageToken']);

        return $messages;
    }

    protected function loadAllMessages(): void
    {
        if ($this->allMessages === null) {
            $this->allMessages = $this->fetchFromApi([
                'labelIds'   => ['INBOX'],
                'maxResults' => 100,
            ]);
        }
    }

    protected function loadUnreadMessages(): void
    {
        if ($this->unreadMessages === null) {
            $this->unreadMessages = $this->fetchFromApi([
                'labelIds' => ['UNREAD'],
            ]);
        }
    }

    public function getMessages($criteria = null): array
    {
        if ($criteria === 'UNSEEN') {
            $this->loadUnreadMessages();
            return $this->unreadMessages ?? [];
        }

        $this->loadAllMessages();
        return $this->allMessages ?? [];
    }

    public function getMessage($num)
    {
        if (!is_numeric($num)) {
            // Gmail message ID string — fetch directly from the API
            $gmailMessage = $this->service->users_messages->get($this->userId, $num);
            return new GmailMessageAdapter($gmailMessage, $this->service, $this->userId);
        }

        $this->loadAllMessages();
        return $this->allMessages[(int)$num - 1] ?? null;
    }

    public function count()
    {
        $this->loadAllMessages();
        return count($this->allMessages ?? []);
    }

    public function expunge()
    {
        return true;
    }

    public function delete()
    {
        return true;
    }

    public function getRawEmail($messageId): string
    {
        $message = $this->service->users_messages->get($this->userId, $messageId, ['format' => 'raw']);
        return base64_decode(strtr($message->getRaw(), '-_', '+/'));
    }
}
