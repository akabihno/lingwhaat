<?php

namespace App\Message;

use App\Constant\PatternIndexConstants;

class WikipediaPatternIndexDispatchMessage
{
    public function __construct(
        private readonly int $windowSize = PatternIndexConstants::WINDOW_SIZE,
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
