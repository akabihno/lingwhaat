<?php

namespace App\Exception;

class WikipediaArticleLimitExceededException extends \RuntimeException
{
    public function __construct(string $languageCode, int $count, int $limit)
    {
        parent::__construct(sprintf(
            'Wikipedia articles for language "%s" exceed limit: %d > %d.',
            $languageCode,
            $count,
            $limit
        ));
    }
}
