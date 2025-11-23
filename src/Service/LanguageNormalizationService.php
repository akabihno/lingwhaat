<?php

namespace App\Service;

class LanguageNormalizationService
{
    const int ARTICLE_LENGTH = 3;
    public function normalizeText(string $text): string
    {
        return mb_trim(mb_strtolower($text));
    }

    public function removeArticles(array $words): array
    {
        return array_filter($words, fn($word) => !$this->isShorterThan($word));
    }

    public function normalizeWord(string $word): string
    {
        return $this->removePunctuation($word);
    }

    protected function removePunctuation(string $word): string
    {
        $punctuation = "!\|@#$%^&*()-=_+,./{}'`[]?<>~:;–—“”‘…•\"";

        return mb_trim($word, $punctuation);
    }

    protected function isShorterThan(string $word): bool
    {
        return mb_strlen($word, 'utf8') <= self::ARTICLE_LENGTH;
    }

}