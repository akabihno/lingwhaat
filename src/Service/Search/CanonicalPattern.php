<?php

namespace App\Service\Search;

final class CanonicalPattern
{
    public static function fromString(string $word): string
    {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return self::fromChars($chars);
    }

    /**
     * @param array<int, string> $chars
     */
    public static function fromChars(array $chars): string
    {
        $map = [];
        $nextId = 0;
        $pattern = [];

        foreach ($chars as $char) {
            if (!isset($map[$char])) {
                $map[$char] = $nextId++;
            }

            $pattern[] = $map[$char];
        }

        return implode(',', $pattern);
    }

    /**
     * Pattern of the window $chars[$start .. $start + $length) without slicing the array — the
     * corpus scanner calls this for every window of every article.
     *
     * @param list<string> $chars
     */
    public static function fromWindow(array $chars, int $start, int $length): string
    {
        $map = [];
        $nextId = 0;
        $pattern = [];
        $end = $start + $length;

        for ($i = $start; $i < $end; $i++) {
            $char = $chars[$i];
            if (!isset($map[$char])) {
                $map[$char] = $nextId++;
            }

            $pattern[] = $map[$char];
        }

        return implode(',', $pattern);
    }
}
