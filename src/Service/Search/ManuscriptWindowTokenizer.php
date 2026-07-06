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
        $chars = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($sequences === []) {
            return $chars;
        }

        // Split the text into code points once (O(1) indexed access afterwards) and pre-split each
        // sequence into its own code-point array (parallel to $sequences). The per-position scan is
        // then plain array comparisons — no mb_substr (which is O(i) per call and would make
        // tokenizing a full concatenated manuscript quadratic) and no per-candidate temp string.
        // Sequences arrive longest-first, so the first match is the greediest.
        $sequenceChars = array_map(
            static fn (string $sequence): array => preg_split('//u', $sequence, -1, PREG_SPLIT_NO_EMPTY) ?: [],
            $sequences,
        );

        $total = count($chars);
        $tokens = [];
        $i = 0;

        while ($i < $total) {
            $matched = null;

            foreach ($sequenceChars as $index => $seqChars) {
                $length = count($seqChars);
                if ($length === 0 || $i + $length > $total) {
                    continue;
                }

                $isMatch = true;
                for ($k = 0; $k < $length; ++$k) {
                    if ($chars[$i + $k] !== $seqChars[$k]) {
                        $isMatch = false;
                        break;
                    }
                }

                if ($isMatch) {
                    $matched = $sequences[$index];
                    $i += $length;
                    break;
                }
            }

            if ($matched === null) {
                $tokens[] = $chars[$i];
                ++$i;
            } else {
                $tokens[] = $matched;
            }
        }

        return $tokens;
    }
}
