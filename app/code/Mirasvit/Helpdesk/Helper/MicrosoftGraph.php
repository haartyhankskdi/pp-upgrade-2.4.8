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
 * Thin Microsoft Graph REST client for mailbox access (delegated, /me).
 *
 * Microsoft 365 mail cannot be fetched over IMAP on this stack: PHP's imap extension is built
 * against UW c-Client 2007f, which has no XOAUTH2/SASL-OAuth support, and Microsoft has disabled
 * basic LOGIN. So - exactly as the Gmail gateway uses the Google API instead of imap_open - the
 * Microsoft gateway fetches through Graph. This client is intentionally small and framework-free
 * (raw cURL) because it needs PATCH/DELETE and a raw-MIME ($value) download that the shared
 * Magento HTTP client does not expose cleanly, and because a fresh handle per call avoids the
 * stateful-option pitfalls of a reused client.
 */
class MicrosoftGraph
{
    const BASE_URL = 'https://graph.microsoft.com/v1.0';

    const CONNECT_TIMEOUT = 10;

    const REQUEST_TIMEOUT = 30;

    /**
     * List messages in the Inbox, newest first, as lightweight metadata rows.
     *
     * @param string $accessToken
     * @param int    $top
     * @return array[] each row: ['id' => string, 'internetMessageId' => string, 'isRead' => bool]
     */
    public function listInboxMessages($accessToken, $top = 100)
    {
        $query = http_build_query([
            '$select'  => 'id,internetMessageId,isRead,receivedDateTime',
            '$orderby' => 'receivedDateTime desc',
            '$top'     => (int)$top,
        ]);

        $response = $this->sendJson($accessToken, 'GET', '/me/mailFolders/inbox/messages?' . $query);
        $rows     = isset($response['value']) && is_array($response['value']) ? $response['value'] : [];

        return array_map(function ($row) {
            return [
                'id'                => $row['id'] ?? '',
                'internetMessageId' => $row['internetMessageId'] ?? ($row['id'] ?? ''),
                'isRead'            => !empty($row['isRead']),
            ];
        }, $rows);
    }

    /**
     * Fetch a single message with the fields the fetch pipeline needs.
     *
     * @param string $accessToken
     * @param string $id
     * @return array Graph message resource
     */
    public function getMessage($accessToken, $id)
    {
        $query = http_build_query([
            '$select' => 'id,internetMessageId,subject,from,sender,replyTo,toRecipients,'
                . 'ccRecipients,receivedDateTime,body,isRead,hasAttachments,internetMessageHeaders',
        ]);

        return $this->sendJson($accessToken, 'GET', '/me/messages/' . rawurlencode($id) . '?' . $query);
    }

    /**
     * Fetch a message's attachments (file attachments carry base64 contentBytes).
     *
     * @param string $accessToken
     * @param string $id
     * @return array[] Graph attachment resources
     */
    public function getAttachments($accessToken, $id)
    {
        $response = $this->sendJson(
            $accessToken,
            'GET',
            '/me/messages/' . rawurlencode($id) . '/attachments'
        );

        return isset($response['value']) && is_array($response['value']) ? $response['value'] : [];
    }

    /**
     * Download the raw RFC 822 MIME source of a message.
     *
     * @param string $accessToken
     * @param string $id
     * @return string
     */
    public function getRawMime($accessToken, $id)
    {
        return $this->send(
            $accessToken,
            'GET',
            '/me/messages/' . rawurlencode($id) . '/$value',
            null,
            false
        );
    }

    /**
     * Mark a message as read.
     *
     * @param string $accessToken
     * @param string $id
     * @return void
     */
    public function markRead($accessToken, $id)
    {
        $this->sendJson($accessToken, 'PATCH', '/me/messages/' . rawurlencode($id), ['isRead' => true]);
    }

    /**
     * Delete a message (Graph moves it to Deleted Items).
     *
     * @param string $accessToken
     * @param string $id
     * @return void
     */
    public function deleteMessage($accessToken, $id)
    {
        $this->sendJson($accessToken, 'DELETE', '/me/messages/' . rawurlencode($id));
    }

    /**
     * Perform a Graph request and return the decoded JSON body.
     *
     * @param string           $accessToken
     * @param non-empty-string $method
     * @param string           $path path beginning with "/" (relative to BASE_URL)
     * @param array|null       $body JSON body for write requests
     * @return array decoded response ([] for empty 204 bodies)
     * @throws \Exception on transport error or non-2xx HTTP status
     */
    private function sendJson($accessToken, $method, $path, $body = null)
    {
        $responseBody = $this->send($accessToken, $method, $path, $body, true);

        // 204 No Content (PATCH/DELETE) returns an empty body.
        if ($responseBody === '') {
            return [];
        }

        $decoded = json_decode($responseBody, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Perform a Graph request and return the raw response body.
     *
     * @param string           $accessToken
     * @param non-empty-string $method
     * @param string           $path       path beginning with "/" (relative to BASE_URL)
     * @param array|null       $body       JSON body for write requests
     * @param bool             $acceptJson send an Accept: application/json header
     * @return string raw response body
     * @throws \Exception on transport error or non-2xx HTTP status
     */
    private function send($accessToken, $method, $path, $body = null, $acceptJson = true)
    {
        $ch = curl_init(self::BASE_URL . $path);

        $headers = ['Authorization: Bearer ' . $accessToken];
        if ($acceptJson) {
            $headers[] = 'Accept: application/json';
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string)json_encode($body));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $responseBody = curl_exec($ch);
        $status       = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        // No curl_close(): it is a no-op since PHP 8.0 (deprecated in 8.5, which Magento's
        // developer-mode ErrorHandler promotes to an exception); the handle frees itself when
        // $ch goes out of scope at the end of this method.

        if ($responseBody === false) {
            throw new \Exception('Microsoft Graph request failed: ' . $curlError);
        }

        $responseBody = (string)$responseBody;

        if ($status < 200 || $status >= 300) {
            $message = $responseBody;
            $decoded = json_decode($responseBody, true);
            if (is_array($decoded) && isset($decoded['error']['message'])) {
                $message = $decoded['error']['message'];
            }

            throw new \Exception('Microsoft Graph error ' . $status . ': ' . $message);
        }

        return $responseBody;
    }
}
