<?php

namespace App\Service;
use App\Controller\EsuLanguageController;

class TransliterationService
{
    public function __construct(protected EsuLanguageController $esuLanguageController)
    {
    }
    public function transliterate($text): void
    {
        $test = $this->esuLanguageController->getLanguageData();
        echo $test;

        switch ($text) {
            case $this->textIsLatin($text):
                var_dump($text . ' is Latin');
                break;
            case $this->textIsCyrillic($text):
                var_dump($text . ' is Cyrillic');
                break;
            case $this->textIsDevanagari($text):
                var_dump($text . ' is Devanagari');
                break;
            default:
                var_dump($text . 'is unknown');
        }
    }

    protected function textIsLatin($text): bool
    {
        return (bool) preg_match('/\p{Latin}/u', $text);
    }

    protected function textIsCyrillic($text): bool
    {
        return (bool) preg_match('/\p{Cyrillic}/u', $text);
    }

    protected function textIsDevanagari($text): bool
    {
        return (bool) preg_match('/\p{Devanagari}/u', $text);
    }

}