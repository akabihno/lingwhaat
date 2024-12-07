<?php

namespace App\ValueObject\IpaMapping\BaltoSlavic\Slavic\EastSlavic;

use App\ValueObject\MappingValueObject;

class RussianIpaMapping extends MappingValueObject
{
    public function __construct(
        protected array $values = [
            'а' => '[a]',
            'б' => '[bɛ]',
            'в' => '[vɛ]',
            'г' => '[ɡɛ]',
            'д' => '[dɛ]',
            'е' => '[je]',
            'ё' => '[jo]',
            'ж' => '[ʐɛ]',
            'з' => '[zɛ]',
            'и' => '[i]',
            'й' => '[ˈi ˈkratkəjə]',
            'к' => '[ka]',
            'л' => '[ɛlʲ]', // ([ɛɫ])
            'м' => '[ɛm]',
            'н' => '[ɛn]',
            'о' => '[о]',
            'п' => '[pɛ]',
            'р' => '[ɛr]',
            'с' => '[ɛs]',
            'т' => '[tɛ]',
            'у' => '[u]',
            'ф' => '[ɛf]',
            'х' => '[xa]',
            'ц' => '[tsɛ]',
            'ч' => '[tɕe]',
            'ш' => '[ʂa]',
            'щ' => '[ɕːa]',
            'ъ' => '[ˈtvʲɵrdɨj znak]',
            'ы' => '[ɨ]',
            'ь' => '[ˈmʲæxʲkʲɪj znak]',
            'э' => '[ɛ]',
            'ю' => '[ju]',
            'я' => '[ja]'
        ]
    )
    {
    }

}