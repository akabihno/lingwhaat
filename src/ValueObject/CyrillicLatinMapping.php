<?php

namespace App\ValueObject;

class CyrillicLatinMapping
{
    public function __construct(
        protected array $values = [
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'jo',
            'ж' => 'zhe',
            'з' => 'zhe',
            'и' => 'i',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ъ' => '',
            'ы' => '',
            'ь' => '',
            'э' => 'e',
            'ю' => 'ju',
            'я' => 'ja'
        ]
    )
    {
    }

}