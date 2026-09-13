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
 * @package   mirasvit/module-core
 * @version   1.7.20
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



declare(strict_types=1);

namespace Mirasvit\Core\Ai\Service\Gemini;

use Mirasvit\Core\Ai\Service\AiClientInterface;
use Mirasvit\Core\Ai\Service\HeaderService;
use Mirasvit\Core\Service\SerializeService;

class Client implements AiClientInterface
{
    public const HEADER_API_KEY = 'x-goog-api-key';

    private $baseUrl = '';

    private $apiKey  = '';

    private $timeout = null;

    private $headerService;

    public function __construct(HeaderService $headerService)
    {
        $this->headerService = $headerService;
    }

    public function sendRequest(string $endpoint, string $method, array $data = [], array $headers = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $result   = $this->executeHttp($url, $method, $this->buildHeaders($headers), $data);
        $response = $result['response'];

        if ($response === false) {
            throw new \Exception('cURL error: ' . $result['error']);
        }

        $decodedResponse = SerializeService::decode($response);

        if ($decodedResponse === null) {
            throw new \Exception('Invalid JSON response from Gemini API');
        }

        return $decodedResponse;
    }

    /**
     * The raw HTTP round-trip, isolated so tests can drive sendRequest()'s response handling
     * without a live endpoint. The curl block that used to be inline — same options and order. The
     * close was in a finally and stays unconditional here; the empty `if ($httpCode >= 400) {}`
     * block the old flow carried did nothing and is dropped (Gemini surfaces API errors through the
     * decoded body, not the status code).
     *
     * @param string[]     $headers resolved request headers
     * @param array<mixed> $data
     *
     * @return array{response: string|bool, httpCode: int, error: string}
     */
    protected function executeHttp(string $url, string $method, array $headers, array $data): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER         => false,
            CURLOPT_NOBODY         => false,
        ]);

        if ($this->timeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string)SerializeService::encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

        return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
    }

    private function buildHeaders(array $additionalHeaders = []): array
    {
        $headers = [
            self::HEADER_CONTENT_TYPE => self::CONTENT_TYPE_JSON,
            self::HEADER_USER_AGENT   => self::DEFAULT_USER_AGENT,
            self::HEADER_API_KEY      => $this->apiKey,
        ];

        return $this->headerService->normalize($headers, $additionalHeaders);
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }
}
