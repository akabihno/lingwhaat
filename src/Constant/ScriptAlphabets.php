<?php

namespace App\Constant;

class ScriptAlphabets
{
    // Latin script (used by most European languages)
    public const string LATIN_ALPHABET = 'abcdefghijklmnopqrstuvwxyz';

    // Cyrillic script (Russian, Ukrainian, Serbian, etc.)
    public const string CYRILLIC_ALPHABET = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';

    // Georgian script
    public const string GEORGIAN_ALPHABET = 'აბგდევზთიკლმნოპჟრსტუფქღყშჩცძწჭხჯჰ';

    // Greek script
    public const string GREEK_ALPHABET = 'αβγδεζηθικλμνξοπρστυφχψω';

    // Arabic script (basic Arabic letters)
    public const string ARABIC_ALPHABET = 'ابتثجحخدذرزسشصضطظعغفقكلمنهوي';

    // Hebrew script
    public const string HEBREW_ALPHABET = 'אבגדהוזחטיכלמנסעפצקרשת';

    // Devanagari script (Hindi, Sanskrit)
    public const string DEVANAGARI_ALPHABET = 'अआइईउऊऋएऐओऔकखगघङचछजझञटठडढणतथदधनपफबभमयरलवशषसह';

    // Armenian script
    public const string ARMENIAN_ALPHABET = 'աբգդեզէըթժիլխծկհձղճմյնշոչպջռսվտրցւփքօֆ';

    // Bengali script
    public const string BENGALI_ALPHABET = 'অআইঈউঊঋএঐওঔকখগঘঙচছজঝঞটঠডঢণতথদধনপফবভমযরলশষসহ';

    /**
     * Map language codes to their script alphabets
     */
    public const array LANGUAGE_TO_ALPHABET = [
        // Latin script languages
        'en' => self::LATIN_ALPHABET,
        'fr' => self::LATIN_ALPHABET,
        'de' => self::LATIN_ALPHABET,
        'es' => self::LATIN_ALPHABET,
        'it' => self::LATIN_ALPHABET,
        'pt' => self::LATIN_ALPHABET,
        'nl' => self::LATIN_ALPHABET,
        'sv' => self::LATIN_ALPHABET,
        'no' => self::LATIN_ALPHABET,
        'da' => self::LATIN_ALPHABET,
        'is' => self::LATIN_ALPHABET,
        'pl' => self::LATIN_ALPHABET,
        'cs' => self::LATIN_ALPHABET,
        'ro' => self::LATIN_ALPHABET,
        'hu' => self::LATIN_ALPHABET,
        'et' => self::LATIN_ALPHABET,
        'lv' => self::LATIN_ALPHABET,
        'lt' => self::LATIN_ALPHABET,
        'sq' => self::LATIN_ALPHABET,
        'la' => self::LATIN_ALPHABET,
        'tl' => self::LATIN_ALPHABET,
        'af' => self::LATIN_ALPHABET,
        'sw' => self::LATIN_ALPHABET,
        'so' => self::LATIN_ALPHABET,
        'ha' => self::LATIN_ALPHABET,
        'tr' => self::LATIN_ALPHABET,
        'uz' => self::LATIN_ALPHABET,
        'vi' => self::LATIN_ALPHABET,
        'gl' => self::LATIN_ALPHABET,
        'br' => self::LATIN_ALPHABET,
        'gul' => self::LATIN_ALPHABET,
        'odt' => self::LATIN_ALPHABET,
        'dum' => self::LATIN_ALPHABET,

        // Cyrillic script languages
        'ru' => self::CYRILLIC_ALPHABET,
        'uk' => self::CYRILLIC_ALPHABET,
        'sh' => self::CYRILLIC_ALPHABET, // Serbo-Croatian (uses both Latin and Cyrillic, defaulting to Cyrillic)
        'kk' => self::CYRILLIC_ALPHABET,
        'mn' => self::CYRILLIC_ALPHABET,
        'kv' => self::CYRILLIC_ALPHABET, // Komi

        // Georgian script
        'ka' => self::GEORGIAN_ALPHABET,

        // Greek script
        'el' => self::GREEK_ALPHABET,

        // Arabic script
        'ar' => self::ARABIC_ALPHABET,
        'ur' => self::ARABIC_ALPHABET,

        // Hebrew script
        'he' => self::HEBREW_ALPHABET,

        // Devanagari script
        'hi' => self::DEVANAGARI_ALPHABET,
        'pi' => self::DEVANAGARI_ALPHABET, // Pali

        // Armenian script
        'hy' => self::ARMENIAN_ALPHABET,

        // Bengali script
        'bn' => self::BENGALI_ALPHABET,
    ];

    /**
     * Get alphabet for a given language code
     *
     * @param string $languageCode
     * @return string The alphabet for the language, or Latin alphabet as fallback
     */
    public static function getAlphabetForLanguage(string $languageCode): string
    {
        return self::LANGUAGE_TO_ALPHABET[$languageCode] ?? self::LATIN_ALPHABET;
    }

    /**
     * Get all supported language codes with alphabets
     *
     * @return array
     */
    public static function getSupportedLanguageCodes(): array
    {
        return array_keys(self::LANGUAGE_TO_ALPHABET);
    }
}
