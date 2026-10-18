<?php

namespace App\Service\Search;

/**
 * Edit distance capped at 1, as used by the former Elasticsearch fuzzy queries (fuzziness 1,
 * transpositions on): one substitution, insertion, deletion or swap of two adjacent characters.
 * Works on code points, like Lucene's Levenshtein automata.
 */
final class EditDistance
{
    /**
     * 0 if equal, 1 if one edit apart, null if further apart.
     */
    public static function atMostOne(string $a, string $b): ?int
    {
        if ($a === $b) {
            return 0;
        }

        $x = mb_str_split($a);
        $y = mb_str_split($b);
        $lx = count($x);
        $ly = count($y);
        if (abs($lx - $ly) > 1) {
            return null;
        }
        if ($lx < $ly) {
            [$x, $y, $lx, $ly] = [$y, $x, $ly, $lx];
        }

        // First position where they differ.
        $i = 0;
        while ($i < $ly && $x[$i] === $y[$i]) {
            $i++;
        }

        if ($lx > $ly) {
            // One deletion from the longer string at $i.
            return array_slice($x, $i + 1) === array_slice($y, $i) ? 1 : null;
        }

        // Same length: one substitution at $i, or a swap of $i and $i + 1.
        if (array_slice($x, $i + 1) === array_slice($y, $i + 1)) {
            return 1;
        }
        if ($i + 1 < $lx && $x[$i] === $y[$i + 1] && $x[$i + 1] === $y[$i]
            && array_slice($x, $i + 2) === array_slice($y, $i + 2)) {
            return 1;
        }

        return null;
    }
}
