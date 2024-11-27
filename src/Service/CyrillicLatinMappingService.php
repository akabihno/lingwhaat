<?php

namespace App\Service;

use App\ValueObject\CyrillicLatinMapping;

class CyrillicLatinMappingService extends TransliterationMappingService
{
    public function get(): CyrillicLatinMapping
    {
        return new CyrillicLatinMapping();
    }

}