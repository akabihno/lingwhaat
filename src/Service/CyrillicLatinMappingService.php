<?php

namespace App\Service;

use App\ValueObject\IpaMapping\BaltoSlavic\Slavic\EastSlavic\RussianIpaMapping;

class CyrillicLatinMappingService extends TransliterationMappingService
{
    public function get(): RussianIpaMapping
    {
        return new RussianIpaMapping();
    }

}