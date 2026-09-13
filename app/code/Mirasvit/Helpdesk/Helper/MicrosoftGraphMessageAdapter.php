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
 * Message adapter for Microsoft 365 gateways, backed by Microsoft Graph.
 *
 * Mirrors {@see GmailMessageAdapter}: it exposes the getFrom()/getSubject()/getBodyHtml()/...
 * surface the fetch pipeline expects, mapping Microsoft Graph's structured JSON message onto it.
 * The full message (body, recipients, headers) is loaded lazily on first access; the constructor
 * only needs the lightweight listing row (id, internet message id, read flag).
 */
class MicrosoftGraphMessageAdapter
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
     * @var string Graph message id (used for read/delete/raw operations)
     */
    private $graphId;

    /**
     * @var string RFC 822 Message-ID (used by the pipeline for de-duplication)
     */
    private $internetMessageId;

    /**
     * @var bool
     */
    private $isRead;

    /**
     * @var array|null full Graph message, loaded on demand
     */
    private $message = null;

    /**
     * @var array|null attachments, loaded on demand
     */
    private $attachments = null;

    /**
     * @var int
     */
    private $isProcessed = 0;

    /**
     * @param MicrosoftGraph $client
     * @param string         $accessToken
     * @param array          $row listing row: ['id', 'internetMessageId', 'isRead']
     */
    public function __construct(MicrosoftGraph $client, $accessToken, array $row)
    {
        $this->client            = $client;
        $this->accessToken       = $accessToken;
        $this->graphId           = $row['id'] ?? '';
        $this->internetMessageId = $row['internetMessageId'] ?? ($row['id'] ?? '');
        $this->isRead            = !empty($row['isRead']);
    }

    /**
     * @return array
     */
    private function message()
    {
        if ($this->message === null) {
            $this->message = $this->client->getMessage($this->accessToken, $this->graphId);
            $this->isRead  = !empty($this->message['isRead']);
        }

        return $this->message;
    }

    /**
     * The fetch pipeline treats a non-empty getErrors() as "skip this message"; Graph messages
     * never carry parse errors, so this is always empty (parity with GmailMessageAdapter).
     *
     * @return array
     */
    public function getErrors()
    {
        return [];
    }

    /**
     * @return int
     */
    public function getIsProcessed(): int
    {
        return $this->isProcessed;
    }

    /**
     * @param int $isProcessed
     * @return $this
     */
    public function setIsProcessed($isProcessed)
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }

    /**
     * @return string Graph message id
     */
    public function getGraphId()
    {
        return $this->graphId;
    }

    /**
     * @return EmailAddressAdapter|null
     */
    public function getFrom()
    {
        $message = $this->message();
        $from    = $message['from']['emailAddress'] ?? ($message['sender']['emailAddress'] ?? null);

        return $this->toAddressAdapter($from);
    }

    /**
     * @return EmailAddressAdapter|null
     */
    public function getReplyTo()
    {
        $message = $this->message();
        if (empty($message['replyTo'][0]['emailAddress'])) {
            return null;
        }

        return $this->toAddressAdapter($message['replyTo'][0]['emailAddress']);
    }

    /**
     * @return MailRecipient[]
     */
    public function getTo()
    {
        return $this->recipientObjects($this->message()['toRecipients'] ?? []);
    }

    /**
     * @return MailRecipient[]
     */
    public function getCc()
    {
        return $this->recipientObjects($this->message()['ccRecipients'] ?? []);
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return (string)($this->message()['subject'] ?? '');
    }

    /**
     * @return string|null ISO 8601 date (accepted by \DateTime in the pipeline)
     */
    public function getDate()
    {
        return $this->message()['receivedDateTime'] ?? null;
    }

    /**
     * @return string
     */
    public function getBodyHtml()
    {
        $body = $this->message()['body'] ?? [];
        if (($body['contentType'] ?? '') === 'html') {
            return (string)($body['content'] ?? '');
        }

        return '';
    }

    /**
     * @return string
     */
    public function getBodyText()
    {
        $body = $this->message()['body'] ?? [];
        if (($body['contentType'] ?? '') === 'text') {
            return (string)($body['content'] ?? '');
        }

        return '';
    }

    /**
     * Internet headers as an object exposing toString(), matching what the pipeline's
     * auto-response detection ({@see \Mirasvit\Helpdesk\Helper\Fetch::isMessageAutosubmitted})
     * expects. Graph only returns internetMessageHeaders when explicitly selected; when absent,
     * a minimal header block is synthesised so detection degrades to "treat as normal mail".
     *
     * @return object
     */
    public function getHeaders()
    {
        $message = $this->message();
        $lines   = [];

        if (!empty($message['internetMessageHeaders']) && is_array($message['internetMessageHeaders'])) {
            foreach ($message['internetMessageHeaders'] as $header) {
                if (isset($header['name'])) {
                    $lines[$header['name']] = (string)($header['value'] ?? '');
                }
            }
        }

        if (!$lines) {
            $from = $this->getFrom();
            if ($from) {
                $lines['From'] = (string)$from;
            }
            $lines['Subject'] = $this->getSubject();
        }

        return new MailHeaderBag($lines);
    }

    /**
     * @return MicrosoftGraphAttachmentAdapter[]
     */
    public function getAttachments()
    {
        if (!$this->message()['hasAttachments']) {
            return [];
        }

        if ($this->attachments === null) {
            $this->attachments = [];
            foreach ($this->client->getAttachments($this->accessToken, $this->graphId) as $attachment) {
                // Only file attachments carry inline bytes; item/reference attachments are skipped.
                if (($attachment['@odata.type'] ?? '') === '#microsoft.graph.fileAttachment') {
                    $this->attachments[] = new MicrosoftGraphAttachmentAdapter($attachment);
                }
            }
        }

        return $this->attachments;
    }

    /**
     * @return string RFC 822 Message-ID (pipeline de-duplication key)
     */
    public function getId()
    {
        return $this->internetMessageId;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->graphId;
    }

    /**
     * @return bool
     */
    public function isUnread(): bool
    {
        return !$this->isRead;
    }

    /**
     * @return bool
     */
    public function isSeen(): bool
    {
        return $this->isRead;
    }

    /**
     * @return void
     */
    public function markAsRead(): void
    {
        $this->client->markRead($this->accessToken, $this->graphId);
        $this->isRead = true;
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->client->deleteMessage($this->accessToken, $this->graphId);
    }

    /**
     * @return bool
     */
    public function expunge()
    {
        return true;
    }

    /**
     * @param array|null $emailAddress Graph emailAddress node: ['address' => ..., 'name' => ...]
     * @return EmailAddressAdapter|null
     */
    private function toAddressAdapter($emailAddress)
    {
        if (empty($emailAddress['address'])) {
            return null;
        }

        return new EmailAddressAdapter($emailAddress['address'], $emailAddress['name'] ?? null);
    }

    /**
     * Build recipient value objects that satisfy both call sites in Fetch: getAddress() (getTo)
     * and public mailbox/host (getCc).
     *
     * @param array $recipients Graph recipient nodes
     * @return MailRecipient[]
     */
    private function recipientObjects(array $recipients)
    {
        $result = [];
        foreach ($recipients as $recipient) {
            $address = $recipient['emailAddress']['address'] ?? '';
            if ($address === '') {
                continue;
            }

            $result[] = new MailRecipient($address);
        }

        return $result;
    }
}
