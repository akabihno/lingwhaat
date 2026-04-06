<?php

namespace App\Service\Categorization;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIWordCategorizationService extends AbstractWordCategorizationService
{
    private const string OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
    private const string MODEL = 'gpt-4o-mini';
    private const int MAX_TOKENS = 16384;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $openaiApiKey
    ) {
        parent::__construct();
    }

    public function categorize(array $words): array
    {
        if (empty($words)) {
            return [];
        }

        $wordList = implode(', ', $words);

        $response = $this->httpClient->request('POST', self::OPENAI_API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => "Categorize these words: $wordList",
                    ],
                ],
            ],
        ]);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(sprintf(
                'OpenAI API error %d: %s',
                $response->getStatusCode(),
                $response->getContent(false)
            ));
        }

        $data = $response->toArray();
        $content = $data['choices'][0]['message']['content'] ?? '';

        return $this->parseResponse($content);
    }
}
