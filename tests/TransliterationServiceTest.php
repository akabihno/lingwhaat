<?php

namespace Tests;

use App\ValueObject\CyrillicLatinMapping;

class TransliterationServiceTest
{
    public function testTransliteration(): void
    {
        $mapping = new CyrillicLatinMapping();

        var_dump($mapping);
    }

}