<?php

namespace App\Service;

/**
 * Scores a manuscript pattern-match result by verifying the cipher→plaintext
 * mapping against the matched article's language, with no further transformation.
 *
 * Shared scoring logic lives in {@see AbstractManuscriptLanguageScoreService};
 * the Atbash variant is {@see ManuscriptLanguageAtbashScoreService}.
 */
class ManuscriptLanguageScoreService extends AbstractManuscriptLanguageScoreService
{
    #[\Override]
    protected function transformWindow(string $wikiWindow, string $languageCode): string
    {
        return $wikiWindow;
    }
}
