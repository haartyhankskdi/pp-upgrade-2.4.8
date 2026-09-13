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

/**
 * Mailbox adapter for Microsoft 365 gateways, backed by Microsoft Graph.
 *
 * Mirrors {@see GmailMailboxAdapter} so the fetch pipeline is transport-agnostic: it exposes the
 * same getMessages()/getMessage()/count() surface the pipeline already consumes for Gmail and IMAP.
 * The Inbox listing is loaded once (newest-first from Graph, then reversed so index 1 is the oldest
 * of the loaded window - matching the IMAP sequence-number convention the pipeline iterates by).
 */
class MicrosoftGraphMailboxAdapter
{
    /**
     * @var MicrosoftGraph
     */
    private $client;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var int
     */
    private $maxMessages;

    /**
     * @var MicrosoftGraphMessageAdapter[]|null loaded on demand, oldest-first
     */
    private $messages = null;

    /**
     * @param MicrosoftGraph $client
     * @param string         $accessToken
     * @param int            $maxMessages upper bound on messages loaded from the Inbox
     */
    public function __construct(MicrosoftGraph $client, $accessToken, $maxMessages = 100)
    {
        $this->client      = $client;
        $this->accessToken = $accessToken;
        $this->maxMessages = (int)$maxMessages > 0 ? (int)$maxMessages : 100;
    }

    /**
     * Load (once) and return the Inbox messages, oldest-first.
     *
     * @return MicrosoftGraphMessageAdapter[]
     */
    private function messages()
    {
        if ($this->messages === null) {
            $rows = $this->client->listInboxMessages($this->accessToken, $this->maxMessages);

            // Graph returns newest-first; reverse to oldest-first so a 1-based index behaves like
            // an IMAP sequence number (higher index = more recent), which fetchEmails() relies on.
            $rows = array_reverse($rows);

            $this->messages = array_map(function ($row) {
                return new MicrosoftGraphMessageAdapter($this->client, $this->accessToken, $row);
            }, $rows);
        }

        return $this->messages;
    }

    /**
     * @param string|null $criteria 'UNSEEN' returns only unread messages
     * @return MicrosoftGraphMessageAdapter[]
     */
    public function getMessages($criteria = null)
    {
        $messages = $this->messages();

        if ($criteria === 'UNSEEN') {
            return array_values(array_filter($messages, function ($message) {
                return $message->isUnread();
            }));
        }

        return $messages;
    }

    /**
     * @param int|string $num 1-based sequence index, or a Graph message id
     * @return MicrosoftGraphMessageAdapter|null
     */
    public function getMessage($num)
    {
        $messages = $this->messages();

        if (is_numeric($num)) {
            return $messages[(int)$num - 1] ?? null;
        }

        // Graph message id
        foreach ($messages as $message) {
            if ($message->getGraphId() === $num) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->messages());
    }

    /**
     * @return bool
     */
    public function expunge()
    {
        // Graph deletes are immediate (the message is moved to Deleted Items on delete()), so
        // there is no separate expunge step; kept for parity with the IMAP/Gmail mailbox API.
        return true;
    }

    /**
     * @return bool
     */
    public function delete()
    {
        return true;
    }

    /**
     * @param string $messageId Graph message id
     * @return string raw RFC 822 MIME
     */
    public function getRawEmail($messageId)
    {
        return $this->client->getRawMime($this->accessToken, $messageId);
    }
}
