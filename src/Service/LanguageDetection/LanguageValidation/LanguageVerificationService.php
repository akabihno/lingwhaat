<?php

namespace App\Service\LanguageDetection\LanguageValidation;

use App\Service\Cache\RedisCacheService;
use App\Service\Logging\ElasticsearchLogger;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;

class LanguageVerificationService
{
    private const int TOP_WORDS_LIMIT = 10000;
    private const int MIN_WORD_LENGTH = 3;
    private const int MIN_FUZZY_WORD_LENGTH = 5;

    /**
     * Fuzziness is caller-supplied and every extra edit widens the fuzzy candidate window, so it
     * is clamped rather than trusted. The documented range is 0-2.
     */
    private const int MAX_FUZZINESS = 2;

    /**
     * The top-word list per language only changes when the words index is rebuilt, so it is
     * cached rather than re-fetched on every request. Elasticsearch is a single node here and
     * pulling 10k documents per verification is the dominant load this endpoint puts on it.
     */
    private const int TOP_WORDS_CACHE_TTL = 3600;
    private const string TOP_WORDS_CACHE_PREFIX = 'language_verification:top_words:v2:';

    /**
     * Matching runs on a single-byte transcription of the text so that the C implementations of
     * substr()/levenshtein() can be used instead of their O(offset) mb_* equivalents. Byte 0 is
     * avoided and byte 255 is reserved as "character that does not occur in the text", which
     * leaves 254 usable slots. Texts with a wider alphabet than that (long CJK inputs) fall back
     * to comparing UTF-8 slices directly.
     */
    private const int ALPHABET_LIMIT = 254;
    private const string ABSENT_CHARACTER = "\xFF";

    private Client $esClient;
    private string $indexName = 'words_index';

    public function __construct(
        private readonly ElasticsearchLogger $logger,
        private readonly RedisCacheService $cache,
        Client $esClient,
    ) {
        $this->esClient = $esClient;
    }

    /**
     * Verify what percentage of text matches the target language using word matching and fuzzy search
     *
     * The text is normalized to a gapless string of letters and digits, then swept once from left
     * to right taking the longest dictionary word that starts at each position. Positions left
     * uncovered by that sweep are retried against a fuzzy index. Coverage is reported as the
     * share of characters claimed by some dictionary word.
     *
     * @param string $text Input text (can be obfuscated without spaces)
     * @param string $languageCode Target language code
     * @param int $fuzziness Fuzzy matching fuzziness level (0-2, default: 1)
     * @return array Contains 'matchPercentage', 'matchedWords' and 'details'
     */
    public function verifyLanguage(
        string $text,
        string $languageCode,
        int $fuzziness = 1
    ): array
    {
        if (empty($text) || empty($languageCode)) {
            return [
                'matchPercentage' => 0,
                'details' => [
                    'error' => 'Text and language code are required',
                    'textLength' => 0,
                    'matchedCharacters' => 0,
                ]
            ];
        }

        $textChars = $this->normalizeToChars($text);
        $textLength = count($textChars);

        if ($textLength === 0) {
            return [
                'matchPercentage' => 0,
                'details' => [
                    'textLength' => 0,
                    'matchedCharacters' => 0,
                    'ngramsGenerated' => 0,
                ]
            ];
        }

        $topWords = $this->fetchTopWords($languageCode);

        if (empty($topWords)) {
            $this->logger->warning("[LanguageVerificationService] No words found for language: {$languageCode}");
            return [
                'matchPercentage' => 0,
                'matchedWords' => [],
                'details' => [
                    'error' => 'No words found for language',
                    'textLength' => $textLength,
                    'matchedCharacters' => 0,
                ]
            ];
        }

        $fuzziness = max(0, min(self::MAX_FUZZINESS, $fuzziness));

        $alphabet = $this->buildAlphabet($textChars);
        $encodable = $alphabet !== null;

        // With an encodable alphabet every character is one byte, so a character offset is also a
        // byte offset and substr()/levenshtein() operate directly on it. Otherwise the same
        // offsets index the UTF-8 character array and slices are rebuilt the slow way.
        $encodedText = $encodable ? $this->encode($textChars, $alphabet) : '';

        $slice = $encodable
            ? static fn(int $offset, int $length): string => substr($encodedText, $offset, $length)
            : static fn(int $offset, int $length): string => implode('', array_slice($textChars, $offset, $length));

        $charAt = $encodable
            ? static fn(int $offset): string => $encodedText[$offset]
            : static fn(int $offset): string => $textChars[$offset];

        $index = $this->buildWordIndex($topWords, $textLength, $fuzziness, $alphabet);

        /** @var array<int, true> $covered Character positions claimed by some dictionary word */
        $covered = [];
        /** @var array<string, true> $matchedWords Keyed by the dictionary spelling, for dedup */
        $matchedWords = [];

        $this->matchExact($index, $textLength, $slice, $covered, $matchedWords);

        if ($fuzziness > 0) {
            $this->matchFuzzy(
                $index,
                $textLength,
                $fuzziness,
                $encodable,
                $slice,
                $charAt,
                $covered,
                $matchedWords
            );
        }

        $matchedCharacters = count($covered);
        $matchPercentage = round((float) $matchedCharacters / (float) $textLength * 100.0, 2);

        $result = [
            'matchPercentage' => $matchPercentage,
            // Cast back to string: words are collected as array keys to dedupe them, and PHP
            // turns an all-digit key into an int, which the response documents as a string.
            'matchedWords' => array_map(strval(...), array_keys($matchedWords)),
            'details' => [
                'languageCode' => $languageCode,
                'textLength' => $textLength,
                'matchedCharacters' => $matchedCharacters,
                'matchedWordsCount' => count($matchedWords),
                'topWordsChecked' => count($topWords),
                'fuzziness' => $fuzziness,
            ]
        ];

        $this->logger->info("[LanguageVerificationService] Language verification completed", $result['details']);

        return $result;
    }

    /**
     * Sweep the text left to right, claiming the longest dictionary word that starts at each
     * position. One pass over the text costs O(textLength * longestWord) hash lookups, where
     * testing every dictionary word against the text separately would cost O(words * textLength).
     *
     * @param array{exact: array<int, array<string, string>>, fuzzy: array<string, array<int, list<array{0: string, 1: string}>>>, maxExactLength: int} $index
     * @param callable(int, int): string $slice
     * @param array<int, true> $covered
     * @param array<string, true> $matchedWords
     */
    private function matchExact(
        array $index,
        int $textLength,
        callable $slice,
        array &$covered,
        array &$matchedWords
    ): void {
        $exact = $index['exact'];
        $maxLength = $index['maxExactLength'];

        if ($maxLength < self::MIN_WORD_LENGTH) {
            return;
        }

        $offset = 0;

        while ($offset < $textLength) {
            $longest = min($maxLength, $textLength - $offset);
            $claimed = 0;

            for ($length = $longest; $length >= self::MIN_WORD_LENGTH; $length--) {
                if (!isset($exact[$length])) {
                    continue;
                }

                $word = $exact[$length][$slice($offset, $length)] ?? null;

                if ($word !== null) {
                    $matchedWords[$word] = true;
                    for ($position = $offset; $position < $offset + $length; $position++) {
                        $covered[$position] = true;
                    }
                    $claimed = $length;
                    break;
                }
            }

            $offset += $claimed > 0 ? $claimed : 1;
        }
    }

    /**
     * Retry the positions the exact sweep left uncovered against the fuzzy index.
     *
     * Candidates are anchored on character identity rather than tried exhaustively: the index is
     * bucketed by anchor character and word length, so each uncovered position only compares
     * against the handful of words that could plausibly start there. Both the position itself and
     * the one after it are probed, and words are indexed under their first two characters, so a
     * single edit falling on a word's opening character still leaves an anchor to find it by.
     *
     * @param array{exact: array<int, array<string, string>>, fuzzy: array<string, array<int, list<array{0: string, 1: string}>>>, maxExactLength: int} $index
     * @param callable(int, int): string $slice
     * @param callable(int): string $charAt
     * @param array<int, true> $covered
     * @param array<string, true> $matchedWords
     */
    private function matchFuzzy(
        array $index,
        int $textLength,
        int $fuzziness,
        bool $encodable,
        callable $slice,
        callable $charAt,
        array &$covered,
        array &$matchedWords
    ): void {
        $fuzzy = $index['fuzzy'];

        if (empty($fuzzy)) {
            return;
        }

        for ($offset = 0; $offset < $textLength; $offset++) {
            if (isset($covered[$offset])) {
                continue;
            }

            $anchors = [$charAt($offset)];

            if ($offset + 1 < $textLength) {
                $following = $charAt($offset + 1);

                if ($following !== $anchors[0]) {
                    $anchors[] = $following;
                }
            }

            $claimed = 0;

            foreach ($anchors as $anchor) {
                $buckets = $fuzzy[$anchor] ?? null;

                if ($buckets === null) {
                    continue;
                }

                // Buckets are ordered longest word first so a longer reading of the same position wins.
                foreach ($buckets as $wordLength => $entries) {
                    $shortest = max(1, $wordLength - $fuzziness);
                    $longest = min($wordLength + $fuzziness, $textLength - $offset);

                    for ($length = $shortest; $length <= $longest; $length++) {
                        $candidate = $slice($offset, $length);

                        foreach ($entries as [$encodedWord, $word]) {
                            $distance = $encodable
                                ? levenshtein($candidate, $encodedWord)
                                : $this->boundedDistance($candidate, $encodedWord, $fuzziness);

                            if ($distance <= $fuzziness) {
                                $matchedWords[$word] = true;
                                $claimed = $length;
                                break 4;
                            }
                        }
                    }
                }
            }

            if ($claimed > 0) {
                for ($position = $offset; $position < $offset + $claimed; $position++) {
                    $covered[$position] = true;
                }
                $offset += $claimed - 1;
            }
        }
    }

    /**
     * Build the per-request lookup structures from the cached word list.
     *
     * @param list<array{0: string, 1: string}> $topWords Dictionary spelling and its normalized form
     * @param array<string, string>|null $alphabet Character-to-byte map, or null when not encodable
     * @return array{exact: array<int, array<string, string>>, fuzzy: array<string, array<int, list<array{0: string, 1: string}>>>, maxExactLength: int}
     */
    private function buildWordIndex(array $topWords, int $textLength, int $fuzziness, ?array $alphabet): array
    {
        $exact = [];
        $fuzzy = [];
        $maxExactLength = 0;

        foreach ($topWords as [$word, $normalized]) {
            if ($normalized === '') {
                continue;
            }

            $chars = mb_str_split($normalized);
            $length = count($chars);

            if ($length < self::MIN_WORD_LENGTH || $length > $textLength + $fuzziness) {
                continue;
            }

            $key = $alphabet !== null ? $this->encode($chars, $alphabet) : $normalized;

            if ($length <= $textLength) {
                // Words arrive ordered by score, so the first spelling to claim a normalized form
                // is the most popular one.
                $exact[$length][$key] ??= $word;
                $maxExactLength = max($maxExactLength, $length);
            }

            if ($fuzziness > 0 && $length >= self::MIN_FUZZY_WORD_LENGTH) {
                // Anchored on the characters as the fuzzy pass sees them: one byte each when
                // encoded, otherwise whole UTF-8 characters - $key[0] would be a stray leading
                // byte for any multi-byte script. Indexing under the second character as well as
                // the first is what lets an edit land on the word's opening character and still
                // be found; anchoring on the first character alone silently lost those words.
                $anchors = $alphabet !== null ? [$key[0], $key[1]] : [$chars[0], $chars[1]];

                $fuzzy[$anchors[0]][$length][] = [$key, $word];

                if ($anchors[1] !== $anchors[0]) {
                    $fuzzy[$anchors[1]][$length][] = [$key, $word];
                }
            }
        }

        foreach ($fuzzy as $character => $buckets) {
            krsort($buckets);
            $fuzzy[$character] = $buckets;
        }

        return [
            'exact' => $exact,
            'fuzzy' => $fuzzy,
            'maxExactLength' => $maxExactLength,
        ];
    }

    /**
     * Map every distinct character of the text onto a single byte, or return null when the text
     * uses more characters than there are slots. Bytes start at 1 so encoded strings never carry
     * a NUL, and self::ABSENT_CHARACTER is left free to stand for characters the text does not
     * contain - it can never collide with a text byte, so an encoded dictionary word holding one
     * simply fails to match, which is the correct outcome.
     *
     * @param list<string> $textChars
     * @return array<string, string>|null
     */
    private function buildAlphabet(array $textChars): ?array
    {
        $alphabet = [];
        $next = 1;

        foreach ($textChars as $character) {
            if (isset($alphabet[$character])) {
                continue;
            }

            if ($next > self::ALPHABET_LIMIT) {
                return null;
            }

            $alphabet[$character] = chr($next);
            $next++;
        }

        return $alphabet;
    }

    /**
     * @param list<string> $chars
     * @param array<string, string> $alphabet
     */
    private function encode(array $chars, array $alphabet): string
    {
        $encoded = '';

        foreach ($chars as $character) {
            $encoded .= $alphabet[$character] ?? self::ABSENT_CHARACTER;
        }

        return $encoded;
    }

    /**
     * Levenshtein distance over UTF-8 characters, abandoned as soon as it is known to exceed
     * $max. Only used for texts whose alphabet does not fit the byte encoding; everything else
     * goes through the native levenshtein(), which is byte-based and would miscount a multi-byte
     * substitution as several edits.
     *
     * @return int The distance, or $max + 1 if it is known to be greater
     */
    private function boundedDistance(string $a, string $b, int $max): int
    {
        $left = mb_str_split($a);
        $right = mb_str_split($b);
        $leftLength = count($left);
        $rightLength = count($right);

        if (abs($leftLength - $rightLength) > $max) {
            return $max + 1;
        }

        $previous = range(0, $rightLength);

        for ($i = 1; $i <= $leftLength; $i++) {
            $current = array_fill(0, $rightLength + 1, 0);
            $current[0] = $i;
            $best = $i;

            for ($j = 1; $j <= $rightLength; $j++) {
                $cost = $left[$i - 1] === $right[$j - 1] ? 0 : 1;
                $current[$j] = min(
                    $previous[$j] + 1,
                    $current[$j - 1] + 1,
                    $previous[$j - 1] + $cost
                );
                $best = min($best, $current[$j]);
            }

            if ($best > $max) {
                return $max + 1;
            }

            $previous = $current;
        }

        return $previous[$rightLength];
    }

    /**
     * Fetch top N words with highest scores from Elasticsearch
     *
     * @param string $languageCode Target language code
     * @param int $limit Number of top words to fetch
     * @return list<array{0: string, 1: string}> Dictionary spelling and its normalized form, by score descending
     */
    private function fetchTopWords(string $languageCode, int $limit = self::TOP_WORDS_LIMIT): array
    {
        $cacheKey = self::TOP_WORDS_CACHE_PREFIX . $languageCode . ':' . $limit;

        /** @var list<array{0: string, 1: string}>|null $cached */
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $boolQuery = new BoolQuery();

            $languageTerm = new Term();
            $languageTerm->setTerm('languageCode', $languageCode);
            $boolQuery->addFilter($languageTerm);

            $query = new Query($boolQuery);
            $query->setSize($limit);
            $query->setSort(['score' => ['order' => 'desc']]);
            // Only the term itself is used; without this Elasticsearch ships the whole document.
            $query->setSource(['word']);

            $results = $this->esClient->getIndex($this->indexName)->search($query);

            $words = [];

            foreach ($results->getResults() as $result) {
                $word = $result->getSource()['word'] ?? '';

                if ($word === '') {
                    continue;
                }

                // Normalization is cached with the word: doing it per request meant a lowercase
                // and a regex over all 10k terms before any matching could start.
                $words[] = [$word, $this->normalizeText($word)];
            }

            if (!empty($words)) {
                $this->cache->set($cacheKey, $words, self::TOP_WORDS_CACHE_TTL);
            }

            return $words;
        } catch (\Exception $e) {
            $this->logger->error("[LanguageVerificationService] Error fetching top words: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Normalize text by converting to lowercase and removing non-letter characters
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        // Keep only letters and numbers, remove all other characters
        return preg_replace('/[^\p{L}\p{N}]/u', '', $text) ?? $text;
    }

    /**
     * @return list<string> The normalized text as individual UTF-8 characters
     */
    private function normalizeToChars(string $text): array
    {
        $normalized = $this->normalizeText($text);

        return $normalized === '' ? [] : mb_str_split($normalized);
    }
}
