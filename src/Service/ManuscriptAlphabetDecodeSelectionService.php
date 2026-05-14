<?php

namespace App\Service;

use App\Entity\ManuscriptAlphabetDecodeResultEntity;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ManuscriptAlphabetDecodeSelectionService
{
    private const string OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
    private const string MODEL = 'gpt-4o-mini';
    private const int MAX_TOKENS = 512;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $openaiApiKey,
    ) {
    }

    /**
     * @return array{status: string, selected_phrase: ?string}
     */
    public function select(ManuscriptAlphabetDecodeResultEntity $result): array
    {
        $candidates = json_decode($result->getWordCandidates(), true);
        if (!is_array($candidates) || $candidates === []) {
            return ['status' => ManuscriptAlphabetDecodeResultEntity::STATUS_NO_MATCH, 'selected_phrase' => null];
        }

        $userPrompt = $this->buildUserPrompt($candidates, $result->getLanguageCode(), $result->getCipherWords());

        try {
            $response = $this->httpClient->request('POST', self::OPENAI_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'max_tokens' => self::MAX_TOKENS,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return ['status' => ManuscriptAlphabetDecodeResultEntity::STATUS_ERROR, 'selected_phrase' => null];
            }

            $data = $response->toArray();
        } catch (\Throwable) {
            return ['status' => ManuscriptAlphabetDecodeResultEntity::STATUS_ERROR, 'selected_phrase' => null];
        }

        $content = (string) ($data['choices'][0]['message']['content'] ?? '');
        $selection = $this->parseSelection($content, $candidates);

        if ($selection === null) {
            return ['status' => ManuscriptAlphabetDecodeResultEntity::STATUS_NO_MATCH, 'selected_phrase' => null];
        }

        return [
            'status' => ManuscriptAlphabetDecodeResultEntity::STATUS_OK,
            'selected_phrase' => implode(' ', $selection),
        ];
    }

    /**
     * @param array<int, array<int, string>> $candidates
     */
    private function buildUserPrompt(array $candidates, string $languageCode, string $cipherWords): string
    {
        $cipherWordList = explode(' ', trim($cipherWords));
        $lines = ["Language: {$languageCode}", "Cipher words: {$cipherWords}", 'Slot candidates (slot index: cipher_word -> candidates):'];
        foreach ($candidates as $i => $words) {
            $cipher = $cipherWordList[$i] ?? "slot{$i}";
            $lines[] = sprintf('  slot %d (%s): %s', $i, $cipher, implode(', ', $words));
        }
        return implode("\n", $lines);
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are decoding a manuscript fragment encrypted with a simple letter-substitution cipher. The user gives you:
- The cipher words (the encrypted text)
- Slot-by-slot candidates: each slot lists real words in the target language whose letter-pattern matches the cipher word at that position

CRITICAL CONSTRAINT — substitution consistency:
Each cipher letter maps to exactly one target-language letter throughout the entire phrase. If cipher letter X maps to target letter Y in one word, every occurrence of X across ALL selected words must also map to Y. Candidates that violate this constraint must be rejected.

To verify: for every pair of slots, check all cipher letters they share. The corresponding target letters at those positions must be identical across both selected words.

Example: cipher words "mfun kgomp" — letter "m" appears at position 0 in both words. If slot 0 selects a word starting with "ה", then slot 1 must also select a word starting with "ה". A slot-1 word starting with "ת" would be inconsistent and must not be chosen.

After enforcing consistency, among all consistent combinations pick the one that forms the most natural and grammatical phrase in the given language. If no consistent combination forms a meaningful phrase, return null.

Respond with ONLY a JSON object:
  {"selection": ["word_for_slot_0", "word_for_slot_1", ...]}
or
  {"selection": null}

No explanation, no markdown.
PROMPT;
    }

    /**
     * @param array<int, array<int, string>> $candidates
     * @return array<int, string>|null
     */
    private function parseSelection(string $content, array $candidates): ?array
    {
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/s', $content, $m)) {
            $json = $m[1];
        } elseif (preg_match('/(\{[\s\S]*\})/s', $content, $m)) {
            $json = $m[1];
        } else {
            return null;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || !array_key_exists('selection', $decoded)) {
            return null;
        }

        $selection = $decoded['selection'];
        if ($selection === null) {
            return null;
        }

        if (!is_array($selection) || count($selection) !== count($candidates)) {
            return null;
        }

        $result = [];
        foreach ($selection as $i => $word) {
            if (!is_string($word) || !in_array($word, $candidates[$i] ?? [], true)) {
                return null;
            }
            $result[] = $word;
        }

        return $result;
    }
}
