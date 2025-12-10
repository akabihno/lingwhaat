<?php

namespace App\Message;

class ParseWiktionaryArticlesMessage
{
    public function __construct(
        private readonly string $language,
        private readonly int $limit = 100
    ) {
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
