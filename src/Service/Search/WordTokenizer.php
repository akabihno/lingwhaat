<?php

namespace App\Service\Search;

use IntlBreakIterator;

/**
 * Splits text into lowercased search tokens the way the Elasticsearch `standard` analyzer did for
 * words_index (Lucene StandardTokenizer + LowerCaseFilter), so token lookups in MySQL keep the
 * same matching semantics: a single input word matches a word inside a multi-word dictionary
 * entry, and `/ˈhaʊs/` matches `haʊs`.
 *
 * Both follow Unicode word segmentation (UAX #29), so ICU's word iterator gives the boundaries.
 * Lucene departs from ICU 76 in a few places, corrected here (checked against Elasticsearch's own
 * _analyze output on a random sample of every language):
 *  - Han and Hiragana are one character per token (ICU groups them with a dictionary).
 *  - Katakana and Southeast Asian (Thai, Lao, Myanmar, Khmer) runs stay whole (ICU splits them
 *    with a dictionary).
 *  - A colon between letters does not split (`category:latin`), nor does a digit/Hangul boundary
 *    (`11월`): older UAX #29 rules that Lucene still follows.
 *  - IPA tone letters (`˧˦`) and the Katakana middle dot (`・`) split tokens instead of joining
 *    them (or, for `・`, instead of becoming a token).
 *  - Emoji are tokens (ICU marks them as non-words).
 * Characters newer than Lucene's Unicode tables (e.g. CJK Extension G/H) are tokens here but were
 * dropped by Elasticsearch; keeping them only makes such entries findable.
 * Lowercasing is per code point (simple case mapping), like Java's Character.toLowerCase.
 */
final class WordTokenizer
{
    // Runs of Southeast Asian letters and marks; their punctuation (e.g. Myanmar ၊) still splits.
    private const string SOUTHEAST_ASIAN = '/^(?:(?!\p{P})[\p{Thai}\p{Lao}\p{Myanmar}\p{Khmer}])+$/u';
    // Katakana plus the prolonged sound mark and (semi-)voiced sound marks, which Lucene groups
    // with Katakana.
    private const string KATAKANA = '/^[\p{Katakana}\x{30FC}\x{3099}-\x{309C}]+$/u';
    // Explicit Hiragana ranges: PCRE's \p{Hiragana} also matches by script extension, which would
    // pull in the prolonged sound mark and voiced marks that Lucene keeps with Katakana.
    private const string HAN_OR_HIRAGANA_CHAR = '[\p{Han}\x{3041}-\x{3096}\x{309D}-\x{309F}]';
    private const string SEPARATORS = '/[\x{02E5}-\x{02E9}\x{A700}-\x{A716}\x{30FB}]/u';
    private const string HANGUL = '/[\p{Hangul}]/u';

    /**
     * @return list<string> tokens in text order (duplicates kept)
     */
    public static function tokenize(string $text): array
    {
        // Same byte length is not needed: offsets are never reported.
        $text = preg_replace(self::SEPARATORS, ' ', $text) ?? $text;
        if ($text === '') {
            return [];
        }

        $tokens = [];
        foreach (self::mergedSegments($text) as [$segment, $isWord]) {
            if (!$isWord) {
                continue;
            }

            if (preg_match('/' . self::HAN_OR_HIRAGANA_CHAR . '/u', $segment) === 1) {
                // Han/Hiragana characters stand alone; anything between them (e.g. Katakana)
                // stays grouped.
                foreach (preg_split('/(' . self::HAN_OR_HIRAGANA_CHAR . ')/u', $segment, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $piece) {
                    $tokens[] = self::lower($piece);
                }
                continue;
            }

            $tokens[] = self::lower($segment);
        }

        return $tokens;
    }

    /**
     * ICU word segments with Lucene's joins applied.
     *
     * @return list<array{string, bool}> [segment, isWord]
     */
    private static function mergedSegments(string $text): array
    {
        $iterator = IntlBreakIterator::createWordInstance('root');
        $iterator->setText($text);

        /** @var list<array{string, bool}> $out */
        $out = [];
        $start = $iterator->first();
        for ($end = $iterator->next(); $end !== IntlBreakIterator::DONE; $start = $end, $end = $iterator->next()) {
            $segment = substr($text, $start, $end - $start);
            $isWord = $iterator->getRuleStatus() >= IntlBreakIterator::WORD_NONE_LIMIT
                || preg_match('/^\p{Emoji_Presentation}/u', $segment) === 1;
            $last = count($out) - 1;

            // Rebuild runs that ICU's dictionaries split apart.
            foreach ([self::SOUTHEAST_ASIAN, self::KATAKANA] as $run) {
                if ($last >= 0 && preg_match($run, $segment) === 1 && preg_match($run, $out[$last][0]) === 1) {
                    $out[$last] = [$out[$last][0] . $segment, true];
                    continue 2;
                }
            }
            if (preg_match(self::SOUTHEAST_ASIAN, $segment) === 1) {
                // A lone Southeast Asian piece is a token even when ICU marks it as non-word.
                $isWord = true;
            }

            // letter ':' letter stays one token.
            if ($isWord && $last >= 1 && $out[$last][0] === ':' && $out[$last - 1][1]
                && preg_match('/\p{L}$/u', $out[$last - 1][0]) === 1 && preg_match('/^\p{L}/u', $segment) === 1) {
                $out[$last - 1] = [$out[$last - 1][0] . ':' . $segment, true];
                array_pop($out);
                continue;
            }

            // A digit or letter directly next to Hangul stays one token.
            if ($isWord && $last >= 0 && $out[$last][1]) {
                $prevChar = mb_substr($out[$last][0], -1);
                $nextChar = mb_substr($segment, 0, 1);
                if ((preg_match(self::HANGUL, $prevChar) === 1 || preg_match(self::HANGUL, $nextChar) === 1)
                    && preg_match('/^[\p{L}\p{N}]$/u', $prevChar) === 1 && preg_match('/^[\p{L}\p{N}]$/u', $nextChar) === 1) {
                    $out[$last] = [$out[$last][0] . $segment, true];
                    continue;
                }
            }

            $out[] = [$segment, $isWord];
        }

        return $out;
    }

    private static function lower(string $token): string
    {
        return mb_convert_case($token, MB_CASE_LOWER_SIMPLE, 'UTF-8');
    }
}
