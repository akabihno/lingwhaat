<?php

namespace App\Message;

readonly class WordsPopularityScoreSetMessage
{
    public function __construct(
        private string $languageCode,
        private int    $limit,
        private int    $offset
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