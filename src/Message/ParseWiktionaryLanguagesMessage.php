<?php

namespace App\Message;

class ParseWiktionaryLanguagesMessage
{
    public function __construct(
        private readonly int $limit = 400,
    ) {
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
