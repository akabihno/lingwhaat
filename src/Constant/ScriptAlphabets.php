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
     * Vowels (or vowel-equivalent glyphs) for each script.
     * For abjads (Arabic, Hebrew) these are the long-vowel letters written in unvowelized text;
     * the ratio thresholds in LanguageValidationService may need per-script tuning.
     */
    public const array SCRIPT_VOWELS = [
        self::LATIN_ALPHABET      => 'aeiou',
        self::CYRILLIC_ALPHABET   => 'аеёиоуыэюя',
        self::GEORGIAN_ALPHABET   => 'აეიოუ',
        self::GREEK_ALPHABET      => 'αεηιοιω',
        self::ARABIC_ALPHABET     => 'اوي',
        self::HEBREW_ALPHABET     => 'אוי',
        self::DEVANAGARI_ALPHABET => 'अआइईउऊऋएऐओऔ',
        self::ARMENIAN_ALPHABET   => 'աեէըիոօ',
        self::BENGALI_ALPHABET    => 'অআইঈউঊঋএঐওঔ',
    ];

    public static function getVowelsForLanguage(string $languageCode): string
    {
        $alphabet = self::LANGUAGE_TO_ALPHABET[$languageCode] ?? self::LATIN_ALPHABET;
        return self::SCRIPT_VOWELS[$alphabet] ?? 'aeiou';
    }

    /**
     * Per-script thresholds for LanguageValidationService.
     *
     * Keys
     * ─────────────────────────────────────────────────────────────────────────
     * min_vowel_ratio        – below this the vowel-ratio score is penalised
     * max_vowel_ratio        – above this the vowel-ratio score is penalised
     * optimal_vowel_ratio    – ratio that scores 100
     * max_consonant_cluster  – consonant runs ≤ this length score 100
     * max_vowel_cluster      – vowel runs ≤ this length score 100
     * alternation_ideal_min  – alternation rate lower-bound for score 100
     * alternation_ideal_max  – alternation rate upper-bound for score 100
     * alternation_good_min   – alternation rate lower-bound for score 75
     *
     * Abjad note (Arabic, Hebrew): short vowels are not written, so only a few
     * long-vowel letters appear in running text, making the written vowel ratio
     * far lower than the phonemic vowel ratio.
     *
     * Georgian note: Georgian is famous for extreme consonant clusters
     * (e.g. "გვbrdgvnis" = 7 consecutive consonants), so the cluster threshold
     * is set much higher than for other scripts.
     */
    public const array SCRIPT_THRESHOLDS = [
        self::LATIN_ALPHABET => [
            'min_vowel_ratio'       => 0.20,
            'max_vowel_ratio'       => 0.60,
            'optimal_vowel_ratio'   => 0.40,
            'max_consonant_cluster' => 3,
            'max_vowel_cluster'     => 2,
            'alternation_ideal_min' => 0.50,
            'alternation_ideal_max' => 0.85,
            'alternation_good_min'  => 0.35,
        ],
        self::CYRILLIC_ALPHABET => [
            'min_vowel_ratio'       => 0.20,
            'max_vowel_ratio'       => 0.60,
            'optimal_vowel_ratio'   => 0.40,
            'max_consonant_cluster' => 4,   // Russian "встр-" etc.
            'max_vowel_cluster'     => 2,
            'alternation_ideal_min' => 0.45,
            'alternation_ideal_max' => 0.85,
            'alternation_good_min'  => 0.30,
        ],
        self::GEORGIAN_ALPHABET => [
            'min_vowel_ratio'       => 0.18,
            'max_vowel_ratio'       => 0.55,
            'optimal_vowel_ratio'   => 0.35,
            'max_consonant_cluster' => 7,
            'max_vowel_cluster'     => 2,
            'alternation_ideal_min' => 0.25,
            'alternation_ideal_max' => 0.75,
            'alternation_good_min'  => 0.15,
        ],
        self::GREEK_ALPHABET => [
            'min_vowel_ratio'       => 0.22,
            'max_vowel_ratio'       => 0.62,
            'optimal_vowel_ratio'   => 0.42,
            'max_consonant_cluster' => 3,
            'max_vowel_cluster'     => 2,
            'alternation_ideal_min' => 0.50,
            'alternation_ideal_max' => 0.85,
            'alternation_good_min'  => 0.35,
        ],
        self::ARABIC_ALPHABET => [
            'min_vowel_ratio'       => 0.05,
            'max_vowel_ratio'       => 0.30,
            'optimal_vowel_ratio'   => 0.13,
            'max_consonant_cluster' => 4,
            'max_vowel_cluster'     => 1,
            'alternation_ideal_min' => 0.20,
            'alternation_ideal_max' => 0.60,
            'alternation_good_min'  => 0.10,
        ],
        self::HEBREW_ALPHABET => [
            'min_vowel_ratio'       => 0.05,
            'max_vowel_ratio'       => 0.30,
            'optimal_vowel_ratio'   => 0.15,
            'max_consonant_cluster' => 4,
            'max_vowel_cluster'     => 1,
            'alternation_ideal_min' => 0.20,
            'alternation_ideal_max' => 0.60,
            'alternation_good_min'  => 0.10,
        ],
        self::DEVANAGARI_ALPHABET => [
            // Independent vowel codepoints appear less often than phonemic vowels
            // because most vowels are diacritics (matras) on consonants.
            'min_vowel_ratio'       => 0.10,
            'max_vowel_ratio'       => 0.45,
            'optimal_vowel_ratio'   => 0.25,
            'max_consonant_cluster' => 3,
            'max_vowel_cluster'     => 2,
            'alternation_ideal_min' => 0.35,
            'alternation_ideal_max' => 0.80,
            'alternation_good_min'  => 0.20,
        ],
        self::ARMENIAN_ALPHABET => [
            'min_vowel_ratio'       => 0.20,
            'max_vowel_ratio'       => 0.58,
            'optimal_vowel_ratio'   => 0.38,
            'max_consonant_cluster' => 4,
            'max_vowel_cluster'     => 2,
            'alternation_ideal_min' => 0.45,
            'alternation_ideal_max' => 0.85,
            'alternation_good_min'  => 0.30,
        ],
        self::BENGALI_ALPHABET => [
            'min_vowel_ratio'       => 0.10,
            'max_vowel_ratio'       => 0.45,
            'optimal_vowel_ratio'   => 0.25,
            'max_consonant_cluster' => 3,
            'max_vowel_cluster'     => 2,
            'alternation_ideal_min' => 0.35,
            'alternation_ideal_max' => 0.80,
            'alternation_good_min'  => 0.20,
        ],
    ];

    public static function getThresholdsForLanguage(string $languageCode): array
    {
        $alphabet = self::LANGUAGE_TO_ALPHABET[$languageCode] ?? self::LATIN_ALPHABET;
        return self::SCRIPT_THRESHOLDS[$alphabet] ?? self::SCRIPT_THRESHOLDS[self::LATIN_ALPHABET];
    }

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
