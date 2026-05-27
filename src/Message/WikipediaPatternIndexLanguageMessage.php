<?php

namespace App\Message;

class WikipediaPatternIndexLanguageMessage
{
    public function __construct(
        private readonly string $languageCode,
        private readonly int $windowSize,
        private readonly int $articleLimit,
    ) {
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getWindowSize(): int
    {
        return $this->windowSize;
    }

    public function getArticleLimit(): int
    {
        return $this->articleLimit;
    }
}
