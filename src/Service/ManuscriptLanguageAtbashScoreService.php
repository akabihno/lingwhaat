<?php

namespace App\Service;

use App\Constant\ScriptAlphabets;

/**
 * Scores a manuscript pattern-match result under the assumption that the
 * plaintext was additionally enciphered with Atbash in the target language.
 *
 * Atbash (like any monoalphabetic substitution) preserves the canonical letter
 * pattern the search matches on, so it cannot be detected at match time. Instead
 * it is applied to the matched real-language window before the cipher→plaintext
 * mapping is built: the letters assigned to the manuscript's fake characters
 * become Atbash(plaintext) over the target language's alphabet. The resulting
 * score is persisted to manuscript_pattern_match_result.language_score_atbash.
 */
class ManuscriptLanguageAtbashScoreService extends AbstractManuscriptLanguageScoreService
{
    #[\Override]
    protected function transformWindow(string $wikiWindow, string $languageCode): string
    {
        return $this->applyAtbash($wikiWindow, $languageCode);
    }

    /**
     * Atbash maps the i-th letter of an alphabet to the (n-1-i)-th letter
     * (first↔last, second↔second-last, …). Characters outside the language's
     * alphabet are left unchanged.
     */
    private function applyAtbash(string $text, string $languageCode): string
    {
        $alphabet = ScriptAlphabets::getAlphabetForLanguage($languageCode);
        $letters = preg_split('//u', $alphabet, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $count = count($letters);

        if ($count === 0) {
            return $text;
        }

        $map = [];
        for ($i = 0; $i < $count; $i++) {
            $map[$letters[$i]] = $letters[$count - 1 - $i];
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode('', array_map(fn($ch) => $map[$ch] ?? $ch, $chars));
    }
}
