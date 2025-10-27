<?php

namespace App\Constant;

use App\Repository\UniquePatternRepository;
use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\LanguageDetection\LanguageServices\AfarLanguageService;
use App\Service\LanguageDetection\LanguageServices\AfrikaansLanguageService;
use App\Service\LanguageDetection\LanguageServices\AlbanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\ArmenianLanguageService;
use App\Service\LanguageDetection\LanguageServices\BengaliLanguageService;
use App\Service\LanguageDetection\LanguageServices\CzechLanguageService;
use App\Service\LanguageDetection\LanguageServices\DutchLanguageService;
use App\Service\LanguageDetection\LanguageServices\EnglishLanguageService;
use App\Service\LanguageDetection\LanguageServices\EstonianLanguageService;
use App\Service\LanguageDetection\LanguageServices\FrenchLanguageService;
use App\Service\LanguageDetection\LanguageServices\GeorgianLanguageService;
use App\Service\LanguageDetection\LanguageServices\GermanLanguageService;
use App\Service\LanguageDetection\LanguageServices\GreekLanguageService;
use App\Service\LanguageDetection\LanguageServices\HindiLanguageService;
use App\Service\LanguageDetection\LanguageServices\ItalianLanguageService;
use App\Service\LanguageDetection\LanguageServices\LatinLanguageService;
use App\Service\LanguageDetection\LanguageServices\LatvianLanguageService;
use App\Service\LanguageDetection\LanguageServices\LithuanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\PolishLanguageService;
use App\Service\LanguageDetection\LanguageServices\PortugueseLanguageService;
use App\Service\LanguageDetection\LanguageServices\RomanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\RussianLanguageService;
use App\Service\LanguageDetection\LanguageServices\SerboCroatianLanguageService;
use App\Service\LanguageDetection\LanguageServices\SpanishLanguageService;
use App\Service\LanguageDetection\LanguageServices\SwedishLanguageService;
use App\Service\LanguageDetection\LanguageServices\TagalogLanguageService;
use App\Service\LanguageDetection\LanguageServices\TurkishLanguageService;
use App\Service\LanguageDetection\LanguageServices\UkrainianLanguageService;

class LanguageServicesAndCodes
{
    public const string FRENCH_LANGUAGE_NAME = 'French';
    public const string FRENCH_LANGUAGE_CODE = 'fr';
    public const string GERMAN_LANGUAGE_NAME = 'German';
    public const string GERMAN_LANGUAGE_CODE = 'de';
    public const string GREEK_LANGUAGE_NAME = 'Greek';
    public const string GREEK_LANGUAGE_CODE = 'el';
    public const string ITALIAN_LANGUAGE_NAME = 'Italian';
    public const string ITALIAN_LANGUAGE_CODE = 'it';
    public const string LATVIAN_LANGUAGE_NAME = 'Latvian';
    public const string LATVIAN_LANGUAGE_CODE = 'lv';
    public const string LITHUANIAN_LANGUAGE_NAME = 'Lithuanian';
    public const string LITHUANIAN_LANGUAGE_CODE = 'lt';
    public const string POLISH_LANGUAGE_NAME = 'Polish';
    public const string POLISH_LANGUAGE_CODE = 'pl';
    public const string PORTUGUESE_LANGUAGE_NAME = 'Portuguese';
    public const string PORTUGUESE_LANGUAGE_CODE = 'pt';
    public const string ROMANIAN_LANGUAGE_NAME = 'Romanian';
    public const string ROMANIAN_LANGUAGE_CODE = 'ro';
    public const string RUSSIAN_LANGUAGE_NAME = 'Russian';
    public const string RUSSIAN_LANGUAGE_CODE = 'ru';
    public const string SERBOCROATIAN_LANGUAGE_NAME = 'Serbo-Croatian';
    public const string SERBOCROATIAN_LANGUAGE_CODE = 'sh';
    public const string TAGALOG_LANGUAGE_NAME = 'Tagalog';
    public const string TAGALOG_LANGUAGE_CODE = 'tl';
    public const string UKRAINIAN_LANGUAGE_NAME = 'Ukrainian';
    public const string UKRAINIAN_LANGUAGE_CODE = 'uk';
    public const string SPANISH_LANGUAGE_NAME = 'Spanish';
    public const string SPANISH_LANGUAGE_CODE = 'es';
    public const string LATIN_LANGUAGE_NAME = 'Latin';
    public const string LATIN_LANGUAGE_CODE = 'la';
    public const string SWEDISH_LANGUAGE_NAME = 'Swedish';
    public const string SWEDISH_LANGUAGE_CODE = 'sv';
    public const string ESTONIAN_LANGUAGE_NAME = 'Estonian';
    public const string ESTONIAN_LANGUAGE_CODE = 'et';
    public const string ENGLISH_LANGUAGE_NAME = 'English';
    public const string ENGLISH_LANGUAGE_CODE = 'en';
    public const string DUTCH_LANGUAGE_NAME = 'Dutch';
    public const string DUTCH_LANGUAGE_CODE = 'nl';
    public const string HINDI_LANGUAGE_NAME = 'Hindi';
    public const string HINDI_LANGUAGE_CODE = 'hi';
    public const string GEORGIAN_LANGUAGE_NAME = 'Georgian';
    public const string GEORGIAN_LANGUAGE_CODE = 'ka';
    public const string TURKISH_LANGUAGE_NAME = 'Turkish';
    public const string TURKISH_LANGUAGE_CODE = 'tr';
    public const string ALBANIAN_LANGUAGE_NAME = 'Albanian';
    public const string ALBANIAN_LANGUAGE_CODE = 'sq';
    public const string CZECH_LANGUAGE_NAME = 'Czech';
    public const string CZECH_LANGUAGE_CODE = 'cs';
    public const string AFRIKAANS_LANGUAGE_NAME = 'Afrikaans';
    public const string AFRIKAANS_LANGUAGE_CODE = 'af';
    public const string ARMENIAN_LANGUAGE_NAME = 'Armenian';
    public const string ARMENIAN_LANGUAGE_CODE = 'hy';
    public const string AFAR_LANGUAGE_NAME = 'Afar';
    public const string AFAR_LANGUAGE_CODE = 'aa';
    public const string BENGALI_LANGUAGE_NAME = 'Bengali';
    public const string BENGALI_LANGUAGE_CODE = 'bn';
    public const string UZBEK_LANGUAGE_NAME = 'Uzbek';
    public const string UZBEK_LANGUAGE_CODE = 'uz';

    public const string LANGUAGE_NOT_FOUND = 'Language not found';

    public static function getLanguageCodes(): array
    {
        return [
            self::FRENCH_LANGUAGE_CODE,
            self::GERMAN_LANGUAGE_CODE,
            self::GREEK_LANGUAGE_CODE,
            self::ITALIAN_LANGUAGE_CODE,
            self::LATVIAN_LANGUAGE_CODE,
            self::LITHUANIAN_LANGUAGE_CODE,
            self::POLISH_LANGUAGE_CODE,
            self::PORTUGUESE_LANGUAGE_CODE,
            self::ROMANIAN_LANGUAGE_CODE,
            self::RUSSIAN_LANGUAGE_CODE,
            self::SERBOCROATIAN_LANGUAGE_CODE,
            self::TAGALOG_LANGUAGE_CODE,
            self::UKRAINIAN_LANGUAGE_CODE,
            self::SPANISH_LANGUAGE_CODE,
            self::LATIN_LANGUAGE_CODE,
            self::SWEDISH_LANGUAGE_CODE,
            self::ESTONIAN_LANGUAGE_CODE,
            self::ENGLISH_LANGUAGE_CODE,
            self::DUTCH_LANGUAGE_CODE,
            self::HINDI_LANGUAGE_CODE,
            self::GEORGIAN_LANGUAGE_CODE,
            self::TURKISH_LANGUAGE_CODE,
            self::ALBANIAN_LANGUAGE_CODE,
            self::CZECH_LANGUAGE_CODE,
            self::AFRIKAANS_LANGUAGE_CODE,
            self::ARMENIAN_LANGUAGE_CODE,
            self::AFAR_LANGUAGE_CODE,
            self::BENGALI_LANGUAGE_CODE,
            self::UZBEK_LANGUAGE_CODE,
        ];
    }

    public static function detectLanguageCodeFromEntity(object $entity): ?string
    {
        $class = get_class($entity);
        $map = [
            'AfrikaansLanguageEntity' => self::AFRIKAANS_LANGUAGE_CODE,
            'AlbanianLanguageEntity' => self::ALBANIAN_LANGUAGE_CODE,
            'ArmenianLanguageEntity' => self::ARMENIAN_LANGUAGE_CODE,
            'CzechLanguageEntity' => self::CZECH_LANGUAGE_CODE,
            'DutchLanguageEntity' => self::DUTCH_LANGUAGE_CODE,
            'EnglishLanguageEntity' => self::ENGLISH_LANGUAGE_CODE,
            'EstonianLanguageEntity' => self::ESTONIAN_LANGUAGE_CODE,
            'FrenchLanguageEntity' => self::FRENCH_LANGUAGE_CODE,
            'GeorgianLanguageEntity' => self::GEORGIAN_LANGUAGE_CODE,
            'GermanLanguageEntity' => self::GERMAN_LANGUAGE_CODE,
            'GreekLanguageEntity' => self::GREEK_LANGUAGE_CODE,
            'HindiLanguageEntity' => self::HINDI_LANGUAGE_CODE,
            'ItalianLanguageEntity' => self::ITALIAN_LANGUAGE_CODE,
            'LatinLanguageEntity' => self::LATIN_LANGUAGE_CODE,
            'LatvianLanguageEntity' => self::LATVIAN_LANGUAGE_CODE,
            'LithuanianLanguageEntity' => self::LITHUANIAN_LANGUAGE_CODE,
            'PolishLanguageEntity' => self::POLISH_LANGUAGE_CODE,
            'PortugueseLanguageEntity' => self::PORTUGUESE_LANGUAGE_CODE,
            'RomanianLanguageEntity' => self::ROMANIAN_LANGUAGE_CODE,
            'RussianLanguageEntity' => self::RUSSIAN_LANGUAGE_CODE,
            'SerboCroatianLanguageEntity' => self::SERBOCROATIAN_LANGUAGE_CODE,
            'SpanishLanguageEntity' => self::SPANISH_LANGUAGE_CODE,
            'SwedishLanguageEntity' => self::SWEDISH_LANGUAGE_CODE,
            'TagalogLanguageEntity' => self::TAGALOG_LANGUAGE_CODE,
            'TurkishLanguageEntity' => self::TURKISH_LANGUAGE_CODE,
            'UkrainianLanguageEntity' => self::UKRAINIAN_LANGUAGE_CODE,
            'AfarLanguageEntity' => self::AFAR_LANGUAGE_CODE,
            'BengaliLanguageEntity' => self::BENGALI_LANGUAGE_CODE,
            'UzbekLanguageEntity' => self::UZBEK_LANGUAGE_CODE,
        ];

        foreach ($map as $entityFragment => $code) {
            if (str_contains($class, $entityFragment)) {
                return $code;
            }
        }

        return null;
    }

}