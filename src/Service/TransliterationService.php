<?php

namespace App\Service;
class TransliterationService
{
    public function transliterate($text) {
        $result = '';

        $textArray = str_split($text);

        foreach ($textArray as $letter) {
            var_dump($letter);
        }

        return $result;
    }

}