<?php

namespace Tests;

use App\Service\TransliterationService;
use App\ValueObject\CyrillicLatinMapping;

class TransliterationServiceTest
{
    public function __construct(protected TransliterationService $transliterationService)
    {
    }
    public function testTransliteration(string $text): void
    {
        $result = $this->transliterationService->transliterate($text);
    }

}