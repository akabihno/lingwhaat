<?php

namespace App\Message;

class WikipediaPatternIndexDispatchMessage
{
    public function __construct(
        private readonly int $windowSize = 18,
        private readonly int $articleLimit = 5,
    ) {
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
