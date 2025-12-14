<?php

namespace App\Service\LanguageDetection;

use App\Constant\LanguageMappings;

class ScriptDetectionService
{
    public const string UNKNOWN_SCRIPT = 'Unknown';
    public const string LATIN_SCRIPT = 'Latin';
    public const string CYRILLIC_SCRIPT = 'Cyrillic';
    public const string ARABIC_SCRIPT = 'Arabic';
    public const string DEVANAGARI_SCRIPT = 'Devanagari';
    public const string GREEK_SCRIPT = 'Greek';
    public const string HEBREW_SCRIPT = 'Hebrew';
    public const string ARMENIAN_SCRIPT = 'Armenian';
    public const string GEORGIAN_SCRIPT = 'Georgian';
    public function detect(string $text): string
    {
        $scripts = [
            self::LATIN_SCRIPT => '/\p{'.self::LATIN_SCRIPT.'}/u',
            self::CYRILLIC_SCRIPT => '/\p{'.self::CYRILLIC_SCRIPT.'}/u',
            self::ARABIC_SCRIPT => '/\p{'.self::ARABIC_SCRIPT.'}/u',
            self::DEVANAGARI_SCRIPT => '/\p{'.self::DEVANAGARI_SCRIPT.'}/u',
            self::GREEK_SCRIPT => '/\p{'.self::GREEK_SCRIPT.'}/u',
            self::HEBREW_SCRIPT => '/\p{'.self::HEBREW_SCRIPT.'}/u',
            self::ARMENIAN_SCRIPT => '/\p{'.self::ARMENIAN_SCRIPT.'}/u',
        ];

        foreach ($scripts as $name => $regex) {
            if (preg_match($regex, $text)) {
                return $name;
            }
        }
        return self::UNKNOWN_SCRIPT;
    }

    protected function getLatinScriptLanguageNames(): array
    {
        return [
            LanguageMappings::FRENCH_LANGUAGE_NAME,
            LanguageMappings::GERMAN_LANGUAGE_NAME,
            LanguageMappings::ITALIAN_LANGUAGE_NAME,
            LanguageMappings::LATVIAN_LANGUAGE_NAME,
            LanguageMappings::LITHUANIAN_LANGUAGE_NAME,
            LanguageMappings::POLISH_LANGUAGE_NAME,
            LanguageMappings::PORTUGUESE_LANGUAGE_NAME,
            LanguageMappings::ROMANIAN_LANGUAGE_NAME,
            LanguageMappings::SERBOCROATIAN_LANGUAGE_NAME,
            LanguageMappings::TAGALOG_LANGUAGE_NAME,
            LanguageMappings::SPANISH_LANGUAGE_NAME,
            LanguageMappings::LATIN_LANGUAGE_NAME,
            LanguageMappings::SWEDISH_LANGUAGE_NAME,
            LanguageMappings::ESTONIAN_LANGUAGE_NAME,
            LanguageMappings::ENGLISH_LANGUAGE_NAME,
            LanguageMappings::DUTCH_LANGUAGE_NAME,
            LanguageMappings::TURKISH_LANGUAGE_NAME,
            LanguageMappings::ALBANIAN_LANGUAGE_NAME,
            LanguageMappings::CZECH_LANGUAGE_NAME,
            LanguageMappings::AFRIKAANS_LANGUAGE_NAME,
            LanguageMappings::AFAR_LANGUAGE_NAME,
            LanguageMappings::BRETON_LANGUAGE_NAME,
            LanguageMappings::OLD_DUTCH_LANGUAGE_NAME,
            LanguageMappings::MIDDLE_DUTCH_LANGUAGE_NAME,
            LanguageMappings::NORWEGIAN_LANGUAGE_NAME,
            LanguageMappings::DANISH_LANGUAGE_NAME,
            LanguageMappings::ICELANDIC_LANGUAGE_NAME,
            LanguageMappings::KAZAKH_LANGUAGE_NAME,
            LanguageMappings::UZBEK_LANGUAGE_NAME,
        ];
    }

    protected function getArabicScriptLanguageNames(): array
    {
        return [
            LanguageMappings::ARABIC_LANGUAGE_NAME,
            LanguageMappings::KAZAKH_LANGUAGE_NAME,
            LanguageMappings::UZBEK_LANGUAGE_NAME,
        ];
    }

    protected function getGreekScriptLanguageNames(): array
    {
        return [LanguageMappings::GREEK_LANGUAGE_NAME];
    }

    protected function getCyrillicScriptLanguageNames(): array
    {
        return [
            LanguageMappings::RUSSIAN_LANGUAGE_NAME,
            LanguageMappings::UKRAINIAN_LANGUAGE_NAME,
            LanguageMappings::KAZAKH_LANGUAGE_NAME,
            LanguageMappings::UZBEK_LANGUAGE_NAME,
        ];

    }

    protected function getDevanagariScriptLanguageNames(): array
    {
        return [
            LanguageMappings::HINDI_LANGUAGE_NAME,
            LanguageMappings::BENGALI_LANGUAGE_NAME
        ];
    }

    protected function getArmenianScriptLanguageNames(): array
    {
        return [LanguageMappings::ARMENIAN_LANGUAGE_NAME];

    }

    protected function getGeorgianScriptLanguageNames(): array
    {
        return [LanguageMappings::GEORGIAN_LANGUAGE_NAME];
    }

    protected function getHebrewScriptLanguageNames(): array
    {
        return [LanguageMappings::HEBREW_LANGUAGE_NAME];
    }

    protected function getAllScriptLanguages(): array
    {
        return [
            self::LATIN_SCRIPT => $this->getLatinScriptLanguageNames(),
            self::ARABIC_SCRIPT => $this->getArabicScriptLanguageNames(),
            self::GREEK_SCRIPT => $this->getGreekScriptLanguageNames(),
            self::CYRILLIC_SCRIPT => $this->getCyrillicScriptLanguageNames(),
            self::DEVANAGARI_SCRIPT => $this->getDevanagariScriptLanguageNames(),
            self::ARMENIAN_SCRIPT => $this->getArmenianScriptLanguageNames(),
            self::GEORGIAN_SCRIPT => $this->getGeorgianScriptLanguageNames(),
            self::HEBREW_SCRIPT => $this->getHebrewScriptLanguageNames(),
        ];
    }

    public function getLatinScriptTransliterationCandidates(): array
    {
        $allScripts = $this->getAllScriptLanguages();
        unset($allScripts[self::LATIN_SCRIPT]);
        return $allScripts;
    }

    public function getArabicScriptTransliterationCandidates(): array
    {
        $allScripts = $this->getAllScriptLanguages();
        unset($allScripts[self::ARABIC_SCRIPT]);
        return $allScripts;
    }

    public function getGreekScriptTransliterationCandidates(): array
    {
        $allScripts = $this->getAllScriptLanguages();
        unset($allScripts[self::GREEK_SCRIPT]);
        return $allScripts;
    }

    public function getCyrillicScriptTransliterationCandidates(): array
    {
        $allScripts = $this->getAllScriptLanguages();
        unset($allScripts[self::CYRILLIC_SCRIPT]);
        return $allScripts;
    }

    public function getDevanagariScriptTransliterationCandidates(): array
    {
        $allScripts = $this->getAllScriptLanguages();
        unset($allScripts[self::DEVANAGARI_SCRIPT]);
        return $allScripts;
    }

    public function getArmenianScriptTransliterationCandidates(): array
    {
        $allScripts = $this->getAllScriptLanguages();
        unset($allScripts[self::ARMENIAN_SCRIPT]);
        return $allScripts;
    }

    public function getGeorgianScriptTransliterationCandidates(): array
    {
        $allScripts = $this->getAllScriptLanguages();
        unset($allScripts[self::GEORGIAN_SCRIPT]);
        return $allScripts;
    }

    public function getHebrewScriptTransliterationCandidates(): array
    {
        $allScripts = $this->getAllScriptLanguages();
        unset($allScripts[self::HEBREW_SCRIPT]);
        return $allScripts;
    }

    public function getTransliterationCandidatesByScript(string $script): array
    {
        return match ($script) {
            self::LATIN_SCRIPT => $this->getLatinScriptTransliterationCandidates(),
            self::ARABIC_SCRIPT => $this->getArabicScriptTransliterationCandidates(),
            self::GREEK_SCRIPT => $this->getGreekScriptTransliterationCandidates(),
            self::CYRILLIC_SCRIPT => $this->getCyrillicScriptTransliterationCandidates(),
            self::DEVANAGARI_SCRIPT => $this->getDevanagariScriptTransliterationCandidates(),
            self::ARMENIAN_SCRIPT => $this->getArmenianScriptTransliterationCandidates(),
            self::GEORGIAN_SCRIPT => $this->getGeorgianScriptTransliterationCandidates(),
            self::HEBREW_SCRIPT => $this->getHebrewScriptTransliterationCandidates(),
            default => [],
        };
    }

    /**
     * Get languages that USE the specified script (for transliteration detection)
     * These are the source languages whose models will be used to predict IPA
     */
    public function getLanguagesByScript(string $script): array
    {
        return match ($script) {
            self::LATIN_SCRIPT => [self::LATIN_SCRIPT => $this->getLatinScriptLanguageNames()],
            self::ARABIC_SCRIPT => [self::ARABIC_SCRIPT => $this->getArabicScriptLanguageNames()],
            self::GREEK_SCRIPT => [self::GREEK_SCRIPT => $this->getGreekScriptLanguageNames()],
            self::CYRILLIC_SCRIPT => [self::CYRILLIC_SCRIPT => $this->getCyrillicScriptLanguageNames()],
            self::DEVANAGARI_SCRIPT => [self::DEVANAGARI_SCRIPT => $this->getDevanagariScriptLanguageNames()],
            self::ARMENIAN_SCRIPT => [self::ARMENIAN_SCRIPT => $this->getArmenianScriptLanguageNames()],
            self::GEORGIAN_SCRIPT => [self::GEORGIAN_SCRIPT => $this->getGeorgianScriptLanguageNames()],
            self::HEBREW_SCRIPT => [self::HEBREW_SCRIPT => $this->getHebrewScriptLanguageNames()],
            default => [],
        };
    }

}