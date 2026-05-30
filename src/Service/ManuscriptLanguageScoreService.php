<?php

namespace App\Service;

use App\Entity\ManuscriptPatternMatchResultEntity;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\WikipediaArticleRepository;
use App\Service\LanguageDetection\LanguageValidation\LanguageVerificationService;

class ManuscriptLanguageScoreService
{
    private const int CIPHER_CACHE_LIMIT = 16;
    private const int ARTICLE_CACHE_LIMIT = 256;

    /**
     * Per-worker memo of the concatenated, normalized, char-split cipher text per sourceId.
     * Building this list dominates score() runtime for any source with many match rows;
     * caching it survives across every message this worker handles until --memory-limit
     * recycles the process.
     *
     * @var array<int, list<string>>
     */
    private array $cipherCharsCache = [];

    /**
     * Per-worker memo of normalized Wikipedia article text and its language code per articleId.
     * null cached for missing articles to short-circuit repeat lookups. Hot articles
     * (very common patterns) appear in many results and benefit most.
     *
     * @var array<int, array{text: string, languageCode: string}|null>
     */
    private array $articleTextCache = [];

    public function __construct(
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly WikipediaArticleRepository $articleRepository,
        private readonly LanguageVerificationService $verificationService,
    ) {
    }

    /**
     * Score a result by trying each ES hit:
     * 1. Fetch the Wikipedia article and extract the matched window.
     * 2. Build a cipher→plaintext character mapping from the window.
     * 3. Apply the mapping to the full concatenated manuscript text (all windows for source_id).
     * 4. Verify the resulting text against the article's language.
     *
     * Returns the language_code and language_score of the best-scoring hit.
     *
     * @return array{language_code: string|null, language_score: float}
     */
    public function score(ManuscriptPatternMatchResultEntity $result): array
    {
        $hits = json_decode($result->getResults(), true, 512, JSON_THROW_ON_ERROR);

        if (empty($hits)) {
            return ['language_code' => null, 'language_score' => 0.0];
        }

        $fullCipherChars = $this->getCipherChars($result->getSourceId());

        $bestScore = 0.0;
        $bestLanguage = null;

        foreach ($hits as $hit) {
            // cipher_window is stored per-hit by the search handler
            $cipherWindow = $hit['cipher_window'] ?? '';
            $articleId = (int)($hit['article_id'] ?? 0);
            $localPosition = (int)($hit['local_position'] ?? 0);
            $length = (int)($hit['length'] ?? 0);

            if ($articleId <= 0 || $length <= 0 || mb_strlen($cipherWindow) !== $length) {
                continue;
            }

            $cachedArticle = $this->getArticleCacheEntry($articleId);
            if ($cachedArticle === null) {
                continue;
            }

            $wikiWindow = mb_substr($cachedArticle['text'], $localPosition, $length);

            if (mb_strlen($wikiWindow) !== $length) {
                continue;
            }

            // Build cipher→plaintext mapping from this window's match
            $mapping = [];
            for ($i = 0; $i < $length; $i++) {
                $cipherChar = mb_substr($cipherWindow, $i, 1);
                $wikiChar = mb_substr($wikiWindow, $i, 1);
                $mapping[$cipherChar] ??= $wikiChar;
            }

            // Apply mapping to the full manuscript cipher text
            $translated = implode('', array_map(fn($ch) => $mapping[$ch] ?? $ch, $fullCipherChars));

            $languageCode = $cachedArticle['languageCode'];
            $verification = $this->verificationService->verifyLanguage($translated, $languageCode, 1);
            $score = (float)($verification['matchPercentage'] ?? 0.0);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLanguage = $languageCode;
            }
        }

        return ['language_code' => $bestLanguage, 'language_score' => $bestScore];
    }

    /**
     * @return list<string>
     */
    private function getCipherChars(int $sourceId): array
    {
        if (isset($this->cipherCharsCache[$sourceId])) {
            return $this->cipherCharsCache[$sourceId];
        }

        $allMatches = $this->matchRepository->findBySourceId($sourceId);
        $fullCipherText = implode('', array_map(
            fn($m) => $this->normalize($m->getSourceData()),
            $allMatches,
        ));
        $chars = preg_split('//u', $fullCipherText, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($this->cipherCharsCache) >= self::CIPHER_CACHE_LIMIT) {
            $oldest = array_key_first($this->cipherCharsCache);
            if ($oldest !== null) {
                unset($this->cipherCharsCache[$oldest]);
            }
        }
        $this->cipherCharsCache[$sourceId] = $chars;

        return $chars;
    }

    /**
     * @return array{text: string, languageCode: string}|null
     */
    private function getArticleCacheEntry(int $articleId): ?array
    {
        if (array_key_exists($articleId, $this->articleTextCache)) {
            return $this->articleTextCache[$articleId];
        }

        $article = $this->articleRepository->find($articleId);
        $entry = $article === null
            ? null
            : [
                'text' => $this->normalize($article->getText()),
                'languageCode' => (string) $article->getLanguageCode(),
            ];

        if (count($this->articleTextCache) >= self::ARTICLE_CACHE_LIMIT) {
            $oldest = array_key_first($this->articleTextCache);
            if ($oldest !== null) {
                unset($this->articleTextCache[$oldest]);
            }
        }
        $this->articleTextCache[$articleId] = $entry;

        return $entry;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
