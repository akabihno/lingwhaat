<?php

namespace App\Service;

use App\Constant\ScriptAlphabets;

/**
 * Scores a manuscript pattern-match result under the assumption that the
 * plaintext was additionally enciphered with Atbash in the target language.
 *
 * After building the cipher→plaintext mapping (see
 * {@see AbstractManuscriptLanguageScoreService::score()}), the mapped text is
 * run through Atbash over the target language's alphabet before verification.
 * The result is persisted to manuscript_pattern_match_result.language_score_atbash.
 */
class ManuscriptLanguageAtbashScoreService extends AbstractManuscriptLanguageScoreService
{
    #[\Override]
    protected function transformTranslated(string $translated, string $languageCode): string
    {
        return $this->applyAtbash($translated, $languageCode);
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
