<?php

namespace App\Message;

class ManuscriptAlphabetDecodeDispatchMessage
{
    public function __construct(
        private readonly string $languageCode,
        private readonly int $windowSize = 18,
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
}
