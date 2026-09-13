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

use Google\Service\Gmail\Message as GmailMessage;
use Google\Service\Gmail;

class GmailMessageAdapter
{
    protected $gmailMessage;
    protected $service;
    protected $userId;
    private $isProcessed = 0;

    public function __construct(GmailMessage $gmailMessage, Gmail $service, $userId = 'me')
    {
        set_error_handler([$this, 'saveErroredEmails']);
        $this->gmailMessage = $gmailMessage;
        $this->service = $service;
        $this->userId = $userId;
        restore_error_handler();
    }

    public function getErrors()
    {
        return [];
    }
    public function saveErroredEmails($errno, $errstr)
    {
        $this->setIsProcessed(2);
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

    public function getFrom()
    {
        $from = null;

        if (!empty($this->gmailMessage->payload->headers)) {
            foreach ($this->gmailMessage->payload->headers as $header) {
                if (strtolower($header->name) === 'from') {
                    $from = $header->value;
                    break;
                }
            }
        }

        return $from ? new EmailAddressAdapter($from) : null;
    }

    public function getParts()
    {
        return $this->gmailMessage->getPayload();
    }

    public function getReplyTo()
    {
        $replyTo = null;

        if (!empty($this->gmailMessage->payload->headers)) {
            foreach ($this->gmailMessage->payload->headers as $header) {
                if (strtolower($header->name) === 'reply-to') {
                    $replyTo = $header->value;
                    break;
                }
            }
        }

        return $replyTo ? new EmailAddressAdapter($replyTo) : null;
    }


    public function getDate()
    {
        if (!isset($this->gmailMessage->internalDate)) {
            return null;
        }

        return date('r', $this->gmailMessage->internalDate / 1000);
    }

    public function getName(): string
    {
        $from = $this->getFrom();
        if (!$from) {
            return 'unknown';
        }

        return $from->getName();
    }


    public function getTo(): array
    {
        $toHeader = $this->getHeader('To');
        if (!$toHeader) {
            return [];
        }

        $addresses = explode(',', $toHeader->address);
        $addresses = array_map('trim', $addresses);

        $result = [];
        foreach ($addresses as $email) {
            $result[] = new MailRecipient($email);
        }

        return $result;
    }

    public function getSubject(): string
    {
        foreach ($this->gmailMessage->payload->headers as $header) {
            if (strtolower($header->name) === 'subject') {
                return $header->value;
            }
        }
        return '';
    }

    public function getBodyText()
    {
        return $this->getPartBody('text/plain');
    }

    public function getBodyHtml()
    {
        return $this->getPartBody('text/html');
    }

    protected function getPartBody($mimeType)
    {
        $data = $this->findPartBodyData($this->gmailMessage->payload->parts ?? [], $mimeType);
        if ($data !== null) {
            return base64_decode(strtr($data, '-_', '+/'));
        }

        // Simple message with no parts: body data sits directly on the payload
        if (!empty($this->gmailMessage->payload->body->data)) {
            return base64_decode(strtr($this->gmailMessage->payload->body->data, '-_', '+/'));
        }

        return '';
    }

    protected function findPartBodyData(array $parts, string $mimeType): ?string
    {
        foreach ($parts as $part) {
            if ($part->mimeType === $mimeType && !empty($part->body->data)) {
                return $part->body->data;
            }
            if (!empty($part->parts)) {
                $result = $this->findPartBodyData($part->parts, $mimeType);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        return null;
    }

    public function getId()
    {
        return $this->gmailMessage->getId();
    }

    public function delete()
    {
        $this->service->users_messages->trash($this->userId, $this->gmailMessage->getId());
    }

    public function expunge()
    {
        return true;
    }

    public function getRaw()
    {
        return $this->gmailMessage;
    }

    public function getHeaders()
    {
        $headers = [];

        if (isset($this->gmailMessage->payload->headers)) {
            foreach ($this->gmailMessage->payload->headers as $header) {
                $headers[$header->name] = $header->value;
            }
        }

        return new MailHeaderBag($headers);
    }

    public function toString()
    {
        return $this->getHeaders();
    }

    protected function getHeader($name)
    {
        if (!isset($this->gmailMessage->payload->headers)) {
            return null;
        }

        foreach ($this->gmailMessage->payload->headers as $header) {
            if (strtolower($header->name) === strtolower($name)) {
                return (object)['address' => $this->parseEmailAddress($header->value)];
            }
        }
        return null;
    }

    protected function parseEmailAddress($raw)
    {
        if (preg_match('/<(.+?)>/', $raw, $matches)) {
            return $matches[1];
        }
        return $raw;
    }

    public function getCc()
    {
        $ccHeader = $this->getHeader('Cc');
        if (!$ccHeader) {
            return [];
        }

        $addresses = explode(',', $ccHeader->address);
        $addresses = array_map('trim', $addresses);

        $result = [];
        foreach ($addresses as $email) {
            $result[] = new MailRecipient($email);
        }

        return $result;
    }

    public function getBcc()
    {
        $bccHeader = $this->getHeader('Bcc');
        if (!$bccHeader) {
            return [];
        }

        $addresses = explode(',', $bccHeader->address);
        $addresses = array_map('trim', $addresses);

        $result = [];
        foreach ($addresses as $email) {
            $result[] = new MailRecipient($email);
        }

        return $result;
    }

    public function getAttachments()
    {
        $attachments = [];
        $this->collectAttachmentParts($this->gmailMessage->payload->parts ?? [], $attachments);
        return $attachments;
    }

    /**
     * @param \Google\Service\Gmail\MessagePart[] $parts
     */
    protected function collectAttachmentParts(array $parts, array &$attachments): void
    {
        foreach ($parts as $part) {
            if (!empty($part->filename)) {
                $attachments[] = new GmailAttachmentAdapter([
                    'filename'  => $part->filename,
                    'mimeType'  => $part->mimeType,
                    'body'      => $part->getBody(),
                    'service'   => $this->service,
                    'userId'    => $this->userId,
                    'messageId' => $this->gmailMessage->getId(),
                ]);
            }
            if (!empty($part->parts)) {
                $this->collectAttachmentParts($part->parts, $attachments);
            }
        }
    }

    public function getLabelIds(): array
    {
        return $this->gmailMessage->getLabelIds() ?: [];
    }

    public function isUnread(): bool
    {
        return in_array('UNREAD', $this->getLabelIds(), true);
    }

    public function isSeen(): bool
    {
        return !$this->isUnread();
    }

    public function getNumber(): string
    {
        return $this->gmailMessage->getId();
    }

    public function markAsRead(): void
    {
        $request = new \Google\Service\Gmail\ModifyMessageRequest();
        $request->setRemoveLabelIds(['UNREAD']);
        $this->service->users_messages->modify($this->userId, $this->gmailMessage->getId(), $request);
    }
}
