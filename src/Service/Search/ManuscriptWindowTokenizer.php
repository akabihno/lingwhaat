<?php

namespace App\Service\Search;

/**
 * Splits already-normalized manuscript text into tokens, collapsing configured multi-character
 * sequences (digraphs / ligatures) into a single token via greedy longest-match.
 *
 * With an empty sequence list this is exactly a per-character split, so the canonical pattern is
 * unchanged — that is what lets the character-grouping feature be a strict superset of the legacy
 * behaviour and share a single code path in {@see ManuscriptCorpusSearchService} and the scorers.
 */
final class ManuscriptWindowTokenizer
{
    /**
     * @param list<string> $sequences grouping sequences, pre-normalized and sorted longest-first
     *                                 (see {@see \App\Repository\ManuscriptCharacterGroupRepository})
     * @return list<string> one entry per glyph; multi-character entries are grouped ligatures
     */
    public static function tokenize(string $normalized, array $sequences): array
    {
        if ($sequences === []) {
            return preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        // Precompute each sequence's length once (parallel to $sequences) so the per-position scan
        // is a handful of mb_substr comparisons instead of re-measuring and slicing every candidate.
        $lengths = array_map(static fn (string $sequence): int => mb_strlen($sequence), $sequences);

        $total = mb_strlen($normalized);
        $tokens = [];
        $i = 0;

        while ($i < $total) {
            $matchedLength = 0;

            foreach ($sequences as $index => $sequence) {
                $length = $lengths[$index];
                if ($length === 0 || $i + $length > $total) {
                    continue;
                }

                if (mb_substr($normalized, $i, $length) === $sequence) {
                    $tokens[] = $sequence;
                    $matchedLength = $length;
                    break;
                }
            }

            if ($matchedLength === 0) {
                $tokens[] = mb_substr($normalized, $i, 1);
                $matchedLength = 1;
            }

            $i += $matchedLength;
        }

        return $tokens;
    }
}
