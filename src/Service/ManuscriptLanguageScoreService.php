<?php

namespace App\Service;

use App\Entity\ManuscriptPatternMatchResultEntity;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\WikipediaArticleRepository;
use App\Service\LanguageDetection\LanguageValidation\LanguageVerificationService;

class ManuscriptLanguageScoreService
{
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

        // Concatenate all manuscript windows for this schedule entry to build the full cipher text
        $allMatches = $this->matchRepository->findBySourceId($result->getSourceId());
        $fullCipherText = implode('', array_map(
            fn($m) => $this->normalize($m->getSourceData()),
            $allMatches
        ));
        $fullCipherChars = preg_split('//u', $fullCipherText, -1, PREG_SPLIT_NO_EMPTY) ?: [];

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

            $article = $this->articleRepository->find($articleId);
            if ($article === null) {
                continue;
            }

            $normalizedArticle = $this->normalize($article->getText());
            $wikiWindow = mb_substr($normalizedArticle, $localPosition, $length);

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

            $languageCode = $article->getLanguageCode();
            $verification = $this->verificationService->verifyLanguage($translated, $languageCode, 1);
            $score = (float)($verification['matchPercentage'] ?? 0.0);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLanguage = $languageCode;
            }
        }

        return ['language_code' => $bestLanguage, 'language_score' => $bestScore];
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
