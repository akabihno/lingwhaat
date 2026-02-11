<?php

namespace App\Message;

class ParseWikipediaArticlesMessage
{
    public function __construct(
        private readonly string $languageCode,
        private readonly int $limit = 50
    ) {
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

}