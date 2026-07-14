<?php

namespace App\Service\Search;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

/**
 * Treats an input text as a monoalphabetic substitution cipher and recovers the plaintext
 * decodings that are consistent with the target language's word index.
 *
 * For each whitespace-separated cipher word it looks up, by exact canonical pattern
 * (see {@see CanonicalPattern}), every same-shaped real word in the language via
 * {@see FuzzySearchService::findByPattern()} (exact term match on `pattern` + `languageCode`;
 * no fuzziness). It then keeps only the whole-sentence candidate combinations whose letters
 * agree on a single bijective substitution — the same cipher letter always decodes to the
 * same plaintext letter, and no two cipher letters collapse onto one plaintext letter.
 */
class SubstitutionCipherSolver
{
    /** Upper bound on exact pattern matches fetched per cipher word (highest-scoring first). */
    public const int DEFAULT_CANDIDATES_PER_WORD = 1000;

    /** Upper bound on the number of consistent whole-text decodings returned. */
    public const int DEFAULT_MAX_RESULTS = 100;

    public function __construct(
        private readonly FuzzySearchService $search,
    ) {
    }

    /**
     * @return list<list<string>> each inner list is one plaintext decoding, one word per
     *                            input word in original order, best (highest-scoring) first
     *
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function solve(
        string $languageCode,
        string $text,
        int $candidatesPerWord = self::DEFAULT_CANDIDATES_PER_WORD,
        int $maxResults = self::DEFAULT_MAX_RESULTS,
    ): array {
        $cipherWords = $this->tokenize($text);
        if ($cipherWords === []) {
            return [];
        }

        /** @var array<string, list<string>> $patternCache pattern => candidate plaintext words */
        $patternCache = [];
        $candidatesPerSlot = [];

        foreach ($cipherWords as $cipherWord) {
            $pattern = CanonicalPattern::fromString($cipherWord);

            if (!array_key_exists($pattern, $patternCache)) {
                $words = [];
                foreach ($this->search->findByPattern($pattern, $languageCode, $candidatesPerWord) as $hit) {
                    $word = (string) ($hit['word'] ?? '');
                    if ($word !== '') {
                        $words[] = $word;
                    }
                }
                $patternCache[$pattern] = $words;
            }

            if ($patternCache[$pattern] === []) {
                // No word in this language shares this word's shape, so no consistent
                // decoding of the whole text can exist.
                return [];
            }

            $candidatesPerSlot[] = $patternCache[$pattern];
        }

        // Most-constrained-first: solve the slots with the fewest candidates (typically the long,
        // rare-pattern words) before the short ambiguous ones. Those pin most of the alphabet up
        // front, so every later slot collapses to a handful of compatible candidates — the
        // difference between a search that finishes instantly and one that explores millions of
        // dead-end partial combinations. Results are re-expanded into original word order.
        $order = array_keys($candidatesPerSlot);
        usort($order, fn(int $a, int $b): int => count($candidatesPerSlot[$a]) <=> count($candidatesPerSlot[$b]));

        $results = [];
        $this->collectDecodings($cipherWords, $candidatesPerSlot, $order, 0, [], [], [], $results, $maxResults);

        return $results;
    }

    /**
     * Depth-first walk over the words (in $order, most-constrained first), extending one global
     * bijective letter map and emitting a decoding — re-keyed into original word order — each time
     * a consistent word is chosen for every slot.
     *
     * @param list<string>          $cipherWords
     * @param list<list<string>>    $candidatesPerSlot
     * @param list<int>             $order   slot indices to visit, most-constrained first
     * @param array<string, string> $forward cipher char => plaintext char
     * @param array<string, string> $reverse plaintext char => cipher char
     * @param array<int, string>    $chosen  plaintext word chosen, keyed by original slot index
     * @param list<list<string>>    $results
     */
    private function collectDecodings(
        array $cipherWords,
        array $candidatesPerSlot,
        array $order,
        int $depth,
        array $forward,
        array $reverse,
        array $chosen,
        array &$results,
        int $maxResults,
    ): void {
        if (count($results) >= $maxResults) {
            return;
        }

        if ($depth === count($order)) {
            ksort($chosen);
            $results[] = array_values($chosen);
            return;
        }

        $slot = $order[$depth];
        foreach ($candidatesPerSlot[$slot] as $candidate) {
            $extended = $this->extendMapping($forward, $reverse, $cipherWords[$slot], $candidate);
            if ($extended === null) {
                continue;
            }

            $chosen[$slot] = $candidate;
            $this->collectDecodings(
                $cipherWords,
                $candidatesPerSlot,
                $order,
                $depth + 1,
                $extended['forward'],
                $extended['reverse'],
                $chosen,
                $results,
                $maxResults,
            );
            unset($chosen[$slot]);

            if (count($results) >= $maxResults) {
                return;
            }
        }
    }

    /**
     * Try to fold one cipher word => plaintext word alignment into the running bijection.
     * Returns the grown maps, or null if it would contradict an existing assignment in either
     * direction (same cipher letter to two plaintext letters, or two cipher letters to one).
     *
     * @param array<string, string> $forward
     * @param array<string, string> $reverse
     * @return array{forward: array<string, string>, reverse: array<string, string>}|null
     */
    private function extendMapping(array $forward, array $reverse, string $cipherWord, string $plainWord): ?array
    {
        $cipherChars = preg_split('//u', $cipherWord, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $plainChars = preg_split('//u', mb_strtolower($plainWord), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($cipherChars) !== count($plainChars)) {
            return null;
        }

        foreach ($cipherChars as $i => $c) {
            $p = $plainChars[$i];

            if ((isset($forward[$c]) && $forward[$c] !== $p) || (isset($reverse[$p]) && $reverse[$p] !== $c)) {
                return null;
            }

            $forward[$c] = $p;
            $reverse[$p] = $c;
        }

        return ['forward' => $forward, 'reverse' => $reverse];
    }

    /**
     * Lowercase, split on whitespace, and strip each token down to letters so a word's shape
     * is computed from letters only (trailing punctuation would otherwise never match).
     *
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $rawTokens = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $words = [];
        foreach ($rawTokens as $token) {
            $letters = preg_replace('/[^\p{L}]+/u', '', mb_strtolower($token)) ?? '';
            if ($letters !== '') {
                $words[] = $letters;
            }
        }

        return $words;
    }
}
