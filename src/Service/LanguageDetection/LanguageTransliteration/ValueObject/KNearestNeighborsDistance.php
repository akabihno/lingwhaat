<?php

namespace App\Service\LanguageDetection\LanguageTransliteration\ValueObject;

class KNearestNeighborsDistance
{
    public static function distanceBasedOnLangCode(string $langCode): int
    {
        switch($langCode) {
            case 'fr':
                return 5;
            case 'lv':
                return 2;
            default:
                return 3;
        }
    }
}