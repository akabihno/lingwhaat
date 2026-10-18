<?php

namespace App\Service\Search;

use App\Repository\WordSearchRepository;

/**
 * Exact and fuzzy token lookups on word_search_token, replacing the words_index term and fuzzy
 * queries. Results have the words_index document shape (see {@see WordSearchRepository}).
 *
 * Like the Elasticsearch term/fuzzy queries, the input is used as given (not analyzed): tokens are
 * stored lowercased, so a capitalized input only matches through a fuzzy edit.
 */
class MysqlWordLookup
{
    public function __construct(
        private readonly WordSearchRepository $repository,
    ) {
    }

    /**
     * Entries containing $input as a token: one per language (an entry that is exactly the token
     * first, then the most popular), for every matching language unless $limit caps them.
     *
     * @return list<array{word: string, ipa: string, languageCode: string, score: int, pattern: string, wordLength: int}>
     */
    public function exactMatches(int $field, string $input, ?int $limit = null): array
    {
        if ($input === '' || mb_strlen($input) > 255) {
            return [];
        }

        $matches = $this->repository->findBestEntryPerLanguage($field, $input);

        return $limit === null ? $matches : array_slice($matches, 0, $limit);
    }

    /**
     * Entries containing a token within one edit of $input (fuzziness 1, first character fixed,
     * adjacent swaps count as one edit), ranked: exact token first, then an entry that is exactly
     * the token, then popularity.
     *
     * @return list<array{word: string, ipa: string, languageCode: string, score: int, pattern: string, wordLength: int}>
     */
    public function closestMatches(int $field, string $input, int $limit, ?string $languageCode = null): array
    {
        $length = mb_strlen($input);
        if ($length === 0 || $length > 256) {
            return [];
        }

        $candidates = $this->repository->findBucketTokens($field, mb_substr($input, 0, 1), max(1, $length - 1), $length + 1, $languageCode);
        $tokens = array_values(array_filter($candidates, static fn (string $token): bool => EditDistance::atMostOne($input, $token) !== null));

        return $this->repository->findRankedEntriesByTokens($field, $input, $tokens, $languageCode, $limit);
    }

    public function exists(int $field, string $input, string $languageCode): bool
    {
        return $input !== '' && mb_strlen($input) <= 255 && $this->repository->tokenExists($field, $input, $languageCode);
    }
}
