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

class GmailAttachmentAdapter
{
    private $data;

    public function __construct(array $attachmentData)
    {
        $this->data = $attachmentData;
    }

    public function getFilename(): string
    {
        return $this->data['filename'] ?? 'unknown';
    }

    public function getType(): string
    {
        return $this->data['mimeType'] ?? 'application/octet-stream';
    }

    public function getSize(): int
    {
        /** @var \Google\Service\Gmail\MessagePartBody|array|null $body */
        $body = $this->data['body'] ?? null;
        if (!$body) {
            return 0;
        }
        return is_object($body) ? (int)$body->getSize() : (int)($body['size'] ?? 0);
    }

    public function getDecodedContent(): ?string
    {
        /** @var \Google\Service\Gmail\MessagePartBody|array|null $body */
        $body = $this->data['body'] ?? null;
        if (!$body) {
            return null;
        }

        // Inline data (small attachments)
        $inlineData = is_object($body) ? $body->getData() : ($body['data'] ?? null);
        if (!empty($inlineData)) {
            return base64_decode(strtr($inlineData, '-_', '+/'));
        }

        // Large attachments: Gmail stores content separately, fetch via API
        $attachmentId = is_object($body) ? $body->getAttachmentId() : ($body['attachmentId'] ?? null);
        if ($attachmentId
            && !empty($this->data['service'])
            && !empty($this->data['userId'])
            && !empty($this->data['messageId'])
        ) {
            try {
                $response = $this->data['service']->users_messages_attachments->get(
                    $this->data['userId'],
                    $this->data['messageId'],
                    $attachmentId
                );
                if ($response->getData()) {
                    return base64_decode(strtr($response->getData(), '-_', '+/'));
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
