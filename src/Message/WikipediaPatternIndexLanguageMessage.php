<?php

namespace App\Message;

class WikipediaPatternIndexLanguageMessage
{
    private const string PENDING_MARKER_PREFIX = 'wpi:pending:';

    /**
     * Redis marker key used to dedup dispatch: the dispatcher only enqueues a language
     * whose marker is free, and the handler clears it once the batch completes.
     */
    public static function pendingMarkerKey(string $languageCode): string
    {
        return self::PENDING_MARKER_PREFIX . $languageCode;
    }

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
