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
 * Attachment adapter for Microsoft 365 gateways.
 *
 * Wraps a Microsoft Graph #microsoft.graph.fileAttachment resource in the same
 * getFilename()/getType()/getSize()/getDecodedContent() surface the fetch pipeline uses for
 * Gmail attachments ({@see GmailAttachmentAdapter}). Graph inlines the content as base64
 * `contentBytes`, so no extra request is needed to read it.
 */
class MicrosoftGraphAttachmentAdapter
{
    /**
     * @var array
     */
    private $data;

    /**
     * @param array $attachmentData Graph fileAttachment resource
     */
    public function __construct(array $attachmentData)
    {
        $this->data = $attachmentData;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->data['name'] ?? 'unknown';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->data['contentType'] ?? 'application/octet-stream';
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return (int)($this->data['size'] ?? 0);
    }

    /**
     * @return string|null
     */
    public function getDecodedContent(): ?string
    {
        if (empty($this->data['contentBytes'])) {
            return null;
        }

        $decoded = base64_decode($this->data['contentBytes'], true);

        return $decoded === false ? null : $decoded;
    }
}
