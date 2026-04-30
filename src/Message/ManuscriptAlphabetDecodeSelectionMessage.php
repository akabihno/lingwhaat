<?php

namespace App\Message;

class ManuscriptAlphabetDecodeSelectionMessage
{
    public function __construct(
        private readonly int $resultId,
    ) {
    }

    public function getResultId(): int
    {
        return $this->resultId;
    }
}
