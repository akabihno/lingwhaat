<?php

namespace App\Service\Search;

/**
 * Splits already-normalized manuscript text into tokens, collapsing configured multi-character
 * sequences (digraphs / ligatures) into a single token via greedy longest-match.
 *
 * With an empty sequence list this is exactly a per-character split, so the canonical pattern is
 * unchanged — that is what lets the character-grouping feature be a strict superset of the legacy
 * behaviour and share a single code path in {@see ManuscriptCorpusSearchService}.
 */
final class ManuscriptWindowTokenizer
{
    /**
     * @param array<int, string> $sequences grouping sequences, pre-sorted longest-first
     * @return array<int, string> one entry per glyph; multi-character entries are grouped ligatures
     */
    public static function tokenize(string $normalized, array $sequences): array
    {
        $chars = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($sequences === []) {
            return $chars;
        }

        $count = count($chars);
        $tokens = [];
        $i = 0;

        while ($i < $count) {
            $matched = null;

            foreach ($sequences as $sequence) {
                $length = mb_strlen($sequence);
                if ($length === 0 || $i + $length > $count) {
                    continue;
                }

                if (implode('', array_slice($chars, $i, $length)) === $sequence) {
                    $matched = $sequence;
                    break;
                }
            }

            if ($matched !== null) {
                $tokens[] = $matched;
                $i += mb_strlen($matched);
            } else {
                $tokens[] = $chars[$i];
                ++$i;
            }
        }

        return $tokens;
    }
}
