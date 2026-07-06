<?php

namespace App\Service;

use App\Entity\ManuscriptPatternMatchResultEntity;
use App\Repository\ManuscriptCharacterGroupRepository;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\WikipediaArticleRepository;
use App\Service\LanguageDetection\LanguageValidation\LanguageVerificationService;
use App\Service\Search\ManuscriptWindowTokenizer;

abstract class AbstractManuscriptLanguageScoreService
{
    private const int CIPHER_CACHE_LIMIT = 16;
    private const int ARTICLE_CACHE_LIMIT = 256;

    /**
     * Per-worker memo of the concatenated, normalized cipher text per sourceId, split into glyph
     * tokens (single characters with grouping off; grouped ligatures collapsed to one entry with it
     * on). Building this list dominates score() runtime for any source with many match rows;
     * caching it survives across every message this worker handles until --memory-limit
     * recycles the process.
     *
     * @var array<int, list<string>>
     */
    private array $cipherTokensCache = [];

    /**
     * Per-worker memo of the (normalized, longest-first) grouping sequences per sourceId. Empty for
     * every source while the feature flag is off. Keeps the tokenization of cipher_window per hit
     * consistent with the tokenization the search side used to produce it.
     *
     * @var array<int, list<string>>
     */
    private array $sequencesCache = [];

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
        private readonly ManuscriptCharacterGroupRepository $characterGroupRepository,
        // Must track the search side's MANUSCRIPT_CHARACTER_GROUPING_ENABLED flag: cipher_window was
        // written grouped iff the flag was on, so it must be re-tokenized the same way here to keep
        // the glyph→plaintext alignment (and hence language_score) correct.
        private readonly bool $characterGroupingEnabled = false,
    ) {
    }

    /**
     * Score a result by trying each ES hit:
     * 1. Fetch the Wikipedia article and extract the matched window.
     * 2. Transform the matched window via {@see transformWindow()} (no-op for the plain scorer,
     *    Atbash for the Atbash scorer) so the letters assigned to the manuscript's fake characters
     *    are re-lettered before the mapping is built.
     * 3. Build a cipher→plaintext character mapping from the (transformed) window.
     * 4. Apply the mapping to the full concatenated manuscript text (all windows for source_id).
     * 5. Verify the resulting text against the article's language.
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

        $sourceId = $result->getSourceId();
        $fullCipherTokens = $this->getCipherTokens($sourceId);

        $bestScore = 0.0;
        $bestLanguage = null;

        foreach ($hits as $hit) {
            // cipher_window is stored per-hit by the search handler. Re-tokenize it with this
            // source's groupings so a grouped glyph (e.g. a digraph) counts as a single position —
            // exactly as the search side did when it built the length-$length canonical pattern.
            // With grouping off this yields one token per character, so the length check and the
            // alignment below reduce to the original per-character behaviour.
            $cipherWindow = (string)($hit['cipher_window'] ?? '');
            $articleId = (int)($hit['article_id'] ?? 0);
            $localPosition = (int)($hit['local_position'] ?? 0);
            $length = (int)($hit['length'] ?? 0);

            $cipherTokens = ManuscriptWindowTokenizer::tokenize($cipherWindow, $this->getSequences($sourceId));

            if ($articleId <= 0 || $length <= 0 || count($cipherTokens) !== $length) {
                continue;
            }

            $cachedArticle = $this->getArticleCacheEntry($articleId);
            if ($cachedArticle === null) {
                continue;
            }

            $languageCode = $cachedArticle['languageCode'];

            $wikiWindow = mb_substr($cachedArticle['text'], $localPosition, $length);

            if (mb_strlen($wikiWindow) !== $length) {
                continue;
            }

            // Hook on the assigned letters, not the full translation: the Atbash scorer re-letters
            // this real-language window through Atbash so the mapping becomes cipher→Atbash(plaintext).
            // Applied here so only assigned real-language letters are transformed; the manuscript's
            // own (untranslated) characters pass through the mapping untouched.
            $wikiWindow = $this->transformWindow($wikiWindow, $languageCode);

            // Build glyph→plaintext mapping: each cipher glyph (token) aligns to one wiki character.
            $mapping = [];
            for ($i = 0; $i < $length; $i++) {
                $cipherGlyph = $cipherTokens[$i];
                $wikiChar = mb_substr($wikiWindow, $i, 1);
                $mapping[$cipherGlyph] ??= $wikiChar;
            }

            // Apply mapping to the full manuscript cipher text under the same glyph tokenization.
            $translated = implode('', array_map(fn($glyph) => $mapping[$glyph] ?? $glyph, $fullCipherTokens));

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
     * Hook applied to the matched real-language window before it becomes the
     * cipher→plaintext mapping. The base scorer uses the window as-is; subclasses
     * can re-letter it (e.g. apply Atbash in the target language) to score an
     * alternative reading. Operating on the window — rather than the full
     * translation — keeps the manuscript's own untranslated characters untouched.
     */
    abstract protected function transformWindow(string $wikiWindow, string $languageCode): string;

    /**
     * The full manuscript cipher text for a source, split into glyph tokens (grouped ligatures
     * collapsed to one entry when the feature is on; one entry per character otherwise).
     *
     * @return list<string>
     */
    private function getCipherTokens(int $sourceId): array
    {
        if (isset($this->cipherTokensCache[$sourceId])) {
            return $this->cipherTokensCache[$sourceId];
        }

        $allMatches = $this->matchRepository->findBySourceId($sourceId);
        $fullCipherText = implode('', array_map(
            fn($m) => $this->normalize($m->getSourceData()),
            $allMatches,
        ));
        $tokens = ManuscriptWindowTokenizer::tokenize($fullCipherText, $this->getSequences($sourceId));

        if (count($this->cipherTokensCache) >= self::CIPHER_CACHE_LIMIT) {
            $oldest = array_key_first($this->cipherTokensCache);
            if ($oldest !== null) {
                unset($this->cipherTokensCache[$oldest]);
            }
        }
        $this->cipherTokensCache[$sourceId] = $tokens;

        return $tokens;
    }

    /**
     * Normalized, longest-first grouping sequences for a source — empty while the feature flag is
     * off, so tokenization degrades to a per-character split. Memoized per worker.
     *
     * @return list<string>
     */
    private function getSequences(int $sourceId): array
    {
        if (!$this->characterGroupingEnabled) {
            return [];
        }

        return $this->sequencesCache[$sourceId] ??= $this->characterGroupRepository->findSequencesBySourceId($sourceId);
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

    protected function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
