<?php

namespace App\Service;

class LanguageNormalizationService
{
    public function normalizeText($text): string
    {
        return trim(strtolower($text));
    }

}