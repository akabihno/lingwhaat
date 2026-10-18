<?php

namespace App\Service\Search;

use App\Entity\WordSearchTokenEntity;
use App\Repository\WordSearchRepository;

/**
 * Exact, fuzzy and canonical-pattern word lookups on the word_search tables.
 */
class FuzzySearchService
{
    public function __construct(
        private readonly WordSearchRepository $wordSearchRepository,
        private readonly MysqlWordLookup $mysqlLookup,
    ) {
    }

    /**
     * Entries containing $input as a token: the best entry of every matching language, capped
     * only by an explicit $limit.
     */
    public function findExactMatches(string $input, ?int $limit = null): array
    {
        return $this->mysqlLookup->exactMatches(WordSearchTokenEntity::FIELD_WORD, $input, $limit);
    }

    /**
     * Entries containing a token within one edit of $input (first character fixed), optionally in
     * one language.
     */
    public function findClosestMatches(string $input, int $limit = 5, string $languageCode = ''): array
    {
        return $this->mysqlLookup->closestMatches(WordSearchTokenEntity::FIELD_WORD, $input, $limit, $languageCode !== '' ? $languageCode : null);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByPattern(string $pattern, string $languageCode, int $limit = 30): array
    {
        return $this->wordSearchRepository->findByPattern($pattern, $languageCode, $limit);
    }
}
