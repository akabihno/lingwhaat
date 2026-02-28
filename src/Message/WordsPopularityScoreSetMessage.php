<?php

namespace App\Message;

class WordsPopularityScoreSetMessage
{
    public function __construct(
        private readonly string $languageCode,
        private readonly int    $limit,
        private readonly int $offset
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

    public function getOffset(): int
    {
        return $this->offset;
    }

}