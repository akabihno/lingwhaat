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

    public function removePunctuation(string $word): string
    {
        $punctuation = "!\|@#$%^&*()-=_+,./{}'`[]?<>~:;–—“”‘…•\"";

        return trim($word, $punctuation);
    }

    protected function isShorterThan(string $word): bool
    {
        return mb_strlen($word, 'utf8') <= self::ARTICLE_LENGTH;
    }

}