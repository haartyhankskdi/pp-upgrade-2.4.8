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

namespace Mirasvit\Core\Ai\Service\Claude;

use Mirasvit\Core\Ai\Service\AbstractAiService;
use Mirasvit\Core\Ai\Api\Data\AiRequestInterface;
use Mirasvit\Core\Ai\Api\Data\AiResponseInterface;

class Service extends AbstractAiService
{
    public function getProvider(): string
    {
        return ConfigProvider::PROVIDER_NAME;
    }

    public function getSupportedModels(): array
    {
        $providerConfig = $this->configProvider->getProviderConfig($this->getProvider());

        return array_merge(
            $providerConfig->getAllModels(),
            array_keys(ConfigProvider::DEPRECATED_MODEL_MAP)
        );
    }

    protected function getEndpoint(AiRequestInterface $request): string
    {
        $providerConfig = $this->configProvider->getProviderConfig($this->getProvider());

        return $providerConfig->getEndpoint();
    }

    protected function buildRequestData(AiRequestInterface $request): array
    {
        $model          = $request->getModel() ? : $this->configProvider->getDefaultModel($this->getProvider());
        $providerConfig = $this->configProvider->getProviderConfig($this->getProvider());
        $model          = $providerConfig->resolveModel($model);

        $data = [
            'model' => $model,
        ];

        $systemPrompt = $request->getSystemPrompt();
        if (!empty($systemPrompt)) {
            $data['system'] = $systemPrompt;
        }

        $data['messages'] = [
            [
                'role'    => 'user',
                'content' => $this->buildContent($request),
            ],
        ];

        $maxTokens = $this->getMaxTokens($request);
        if ($maxTokens > 0) {
            $data['max_tokens'] = $maxTokens;
        } else {
            // Claude requires max_tokens field, use model-specific limit if not configured
            $data['max_tokens'] = $providerConfig->getTokenLimitForModel($model);
        }

        if ($this->supportsTemperature($model)) {
            $data['temperature'] = max(0.0, min(1.0, $request->getTemperature()));
        }

        if ($request->getStopSequences()) {
            $stopSequences = explode(',', $request->getStopSequences());

            $data['stop_sequences'] = array_map('trim', $stopSequences);
        }

        if (in_array('web_search', $request->getTools())) {
            $data['tools'][] = [
                'type'     => 'web_search_20250305',
                'name'     => 'web_search',
                'max_uses' => 5,
            ];
        }

        return $data;
    }

    protected function processResponse(array $rawResponse, AiRequestInterface $originalRequest): AiResponseInterface
    {
        $response = $this->responseFactory->create();

        try {
            if (isset($rawResponse['error'])) {
                return $this->createErrorResponse(
                    $rawResponse['error']['message'] ?? (string)__('Claude API error'),
                    0
                );
            }

            if (!isset($rawResponse['content']) || !is_array($rawResponse['content'])) {
                return $this->createErrorResponse(
                    (string)__('Invalid Claude API response: missing content'),
                    0
                );
            }

            $content = $this->extractContent($rawResponse['content']);
            if (empty($content)) {
                return $this->createErrorResponse(
                    (string)__('Empty content received from Claude API'),
                    0
                );
            }

            $response->setStatus(AiResponseInterface::STATUS_SUCCESS);
            $response->setContent($content);
            $response->setProvider($this->getProvider());
            $response->setModel($originalRequest->getModel() ? : $this->configProvider->getDefaultModel($this->getProvider()));
            $response->setCreatedAt(date('Y-m-d H:i:s'));

            if (isset($rawResponse['usage'])) {
                $usage = $rawResponse['usage'];

                // With prompt caching active, `input_tokens` covers the uncached
                // remainder only, so the full prompt is the sum of all three.
                $inputTokens      = (int)($usage['input_tokens'] ?? 0);
                $cacheWriteTokens = (int)($usage['cache_creation_input_tokens'] ?? 0);
                $cacheReadTokens  = (int)($usage['cache_read_input_tokens'] ?? 0);
                $outputTokens     = (int)($usage['output_tokens'] ?? 0);
                $promptTokens     = $inputTokens + $cacheWriteTokens + $cacheReadTokens;

                $response->setUsageData([
                    'input_tokens'                => $inputTokens,
                    'cache_creation_input_tokens' => $cacheWriteTokens,
                    'cache_read_input_tokens'     => $cacheReadTokens,
                    'prompt_tokens'               => $promptTokens,
                    'output_tokens'               => $outputTokens,
                    'total_tokens'                => $promptTokens + $outputTokens,
                ]);
                $response->setPromptTokens($promptTokens);
                $response->setCompletionTokens($outputTokens);
            }

            $metadata = [];
            if (isset($rawResponse['id'])) {
                $metadata['external_id'] = $rawResponse['id'];
            }
            if (!empty($metadata)) {
                $response->setMetadata($metadata);
            }

            if (isset($rawResponse['stop_reason'])) {
                $response->setFinishReason($rawResponse['stop_reason']);
            }

            return $response;

        } catch (\Exception $e) {
            return $this->createErrorResponse(
                sprintf('Claude response processing error: %s', $e->getMessage()),
                0
            );
        }
    }


    protected function extractContent(array $contentArray): string
    {
        $textContent = [];

        foreach ($contentArray as $block) {
            if (isset($block['type']) && $block['type'] === 'text' && isset($block['text'])) {
                $textContent[] = $block['text'];
            }
        }

        return implode(' ', $textContent);
    }

    /**
     * @return string|array<array{type: string, source?: array, text?: string}>
     */
    private function buildContent(AiRequestInterface $request)
    {
        $attachments = $request->getAttachments();

        if (empty($attachments)) {
            return $request->getPrompt();
        }

        $content = [];

        foreach ($attachments as $att) {
            if ($att['type'] === 'image') {
                $content[] = [
                    'type'   => 'image',
                    'source' => $att['source'] === 'url'
                        ? ['type' => 'url', 'url' => $att['url'] ?? '']
                        : ['type' => 'base64', 'media_type' => $att['media_type'], 'data' => $att['data'] ?? ''],
                ];
            } elseif ($att['type'] === 'document') {
                $content[] = [
                    'type'   => 'document',
                    'source' => $att['source'] === 'url'
                        ? ['type' => 'url', 'url' => $att['url'] ?? '']
                        : ['type' => 'base64', 'media_type' => $att['media_type'], 'data' => $att['data'] ?? ''],
                ];
            } elseif ($att['type'] === 'text_file' && $att['source'] === 'base64' && !empty($att['data'])) {
                $textContent = base64_decode($att['data']);

                if ($textContent !== '') {
                    $content[] = [
                        'type' => 'text',
                        'text' => "--- File: " . ($att['filename'] ?? 'file') . " ---\n" . $textContent . "\n--- End of file ---",
                    ];
                }
            }
        }

        $prompt = $request->getPrompt();
        $content[] = [
            'type' => 'text',
            'text' => is_array($prompt) ? implode("\n", $prompt) : $prompt,
        ];

        return $content;
    }

    private function getMaxTokens(AiRequestInterface $request): int
    {
        $requestMaxTokens = $request->getMaxTokens();
        if ($requestMaxTokens !== null && $requestMaxTokens > 0) {
            return $requestMaxTokens;
        }

        $configMaxTokens = $this->configProvider->getMaxTokens();
        if ($configMaxTokens > 0) {
            return $configMaxTokens;
        }

        return 0;
    }

    private function supportsTemperature(string $model): bool
    {
        // Claude Opus 4.8 uses server-side effort control and rejects the
        // deprecated `temperature` parameter.
        return $model !== ConfigProvider::MODEL_CLAUDE_OPUS_4_8;
    }

}
