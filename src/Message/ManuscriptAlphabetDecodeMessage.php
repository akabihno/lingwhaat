<?php

namespace App\Message;

class ManuscriptAlphabetDecodeMessage
{
    public function __construct(
        private readonly int $matchId,
        private readonly string $languageCode,
        private readonly int $windowSize = 18,
    ) {
    }

    public function getMatchId(): int
    {
        return $this->matchId;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getWindowSize(): int
    {
        return $this->windowSize;
    }
}
