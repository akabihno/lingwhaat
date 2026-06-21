<?php

namespace App\Service\Categorization;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WordCategorizationService extends AbstractWordCategorizationService
{
    private const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL = 'claude-opus-4-6';
    private const MAX_TOKENS = 8096;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $anthropicApiKey
    ) {
        parent::__construct();
    }

    #[\Override]
    public function categorize(array $words): array
    {
        if (empty($words)) {
            return [];
        }

        $wordList = implode(', ', $words);

        $response = $this->httpClient->request('POST', self::CLAUDE_API_URL, [
            'headers' => [
                'x-api-key' => $this->anthropicApiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ],
            'json' => [
                'model' => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'system' => $this->systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Categorize these words: $wordList",
                    ],
                ],
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(sprintf(
                'Anthropic API error %d: %s',
                $response->getStatusCode(),
                $response->getContent(false)
            ));
        }

        $data = $response->toArray();
        $content = $data['content'][0]['text'] ?? '';

        return $this->parseResponse($content);
    }
}
