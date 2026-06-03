<?php

namespace App\Message;

class ManuscriptPatternMatchSearchMessage
{
    public function __construct(
        private readonly ?string $languageCode = null,
    ) {
    }

    /**
     * Null = search all per-language indices (legacy fallback / cross-language sweep).
     * Set = search only the matching wikipedia_global_patterns_<code> index.
     */
    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }
}
