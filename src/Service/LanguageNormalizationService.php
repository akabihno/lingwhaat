<?php

namespace App\Service;

class LanguageNormalizationService
{
    const ARTICLE_LENGTH = 3;
    public function normalizeText(string $text): string
    {
        return trim(strtolower($text));
    }

    public function removeArticles(array $words): array
    {
        return array_filter($words, fn($word) => !$this->isShorterThan($word));
    }

    protected function isShorterThan(string $word): bool
    {
        return strlen($word) < self::ARTICLE_LENGTH;
    }

}