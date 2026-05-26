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
}
