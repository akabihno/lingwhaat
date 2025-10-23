<?php

namespace App\Service\LanguageDetection;

use App\Service\LanguageDetection\LanguageTransliteration\TransliterationDetectionService;
use App\Service\LanguageNormalizationService;
use App\Service\Search\FuzzySearchService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class LanguageDetectionService
{
    const string FRENCH_LANGUAGE_NAME = 'French';
    const string FRENCH_LANGUAGE_CODE = 'fr';
    const string GERMAN_LANGUAGE_NAME = 'German';
    const string GERMAN_LANGUAGE_CODE = 'de';
    const string GREEK_LANGUAGE_NAME = 'Greek';
    const string GREEK_LANGUAGE_CODE = 'el';
    const string ITALIAN_LANGUAGE_NAME = 'Italian';
    const string ITALIAN_LANGUAGE_CODE = 'it';
    const string LATVIAN_LANGUAGE_NAME = 'Latvian';
    const string LATVIAN_LANGUAGE_CODE = 'lv';
    const string LITHUANIAN_LANGUAGE_NAME = 'Lithuanian';
    const string LITHUANIAN_LANGUAGE_CODE = 'lt';
    const string POLISH_LANGUAGE_NAME = 'Polish';
    const string POLISH_LANGUAGE_CODE = 'pl';
    const string PORTUGUESE_LANGUAGE_NAME = 'Portuguese';
    const string PORTUGUESE_LANGUAGE_CODE = 'pt';
    const string ROMANIAN_LANGUAGE_NAME = 'Romanian';
    const string ROMANIAN_LANGUAGE_CODE = 'ro';
    const string RUSSIAN_LANGUAGE_NAME = 'Russian';
    const string RUSSIAN_LANGUAGE_CODE = 'ru';
    const string SERBOCROATIAN_LANGUAGE_NAME = 'Serbo-Croatian';
    const string SERBOCROATIAN_LANGUAGE_CODE = 'sh';
    const string TAGALOG_LANGUAGE_NAME = 'Tagalog';
    const string TAGALOG_LANGUAGE_CODE = 'tl';
    const string UKRAINIAN_LANGUAGE_NAME = 'Ukrainian';
    const string UKRAINIAN_LANGUAGE_CODE = 'uk';
    const string SPANISH_LANGUAGE_NAME = 'Spanish';
    const string SPANISH_LANGUAGE_CODE = 'es';
    const string LATIN_LANGUAGE_NAME = 'Latin';
    const string LATIN_LANGUAGE_CODE = 'la';
    const string SWEDISH_LANGUAGE_NAME = 'Swedish';
    const string SWEDISH_LANGUAGE_CODE = 'sv';
    const string ESTONIAN_LANGUAGE_NAME = 'Estonian';
    const string ESTONIAN_LANGUAGE_CODE = 'et';
    const string ENGLISH_LANGUAGE_NAME = 'English';
    const string ENGLISH_LANGUAGE_CODE = 'en';
    const string DUTCH_LANGUAGE_NAME = 'Dutch';
    const string DUTCH_LANGUAGE_CODE = 'nl';
    const string HINDI_LANGUAGE_NAME = 'Hindi';
    const string HINDI_LANGUAGE_CODE = 'hi';
    const string GEORGIAN_LANGUAGE_NAME = 'Georgian';
    const string GEORGIAN_LANGUAGE_CODE = 'ka';
    const string TURKISH_LANGUAGE_NAME = 'Turkish';
    const string TURKISH_LANGUAGE_CODE = 'tr';
    const string ALBANIAN_LANGUAGE_NAME = 'Albanian';
    const string ALBANIAN_LANGUAGE_CODE = 'sq';
    const string CZECH_LANGUAGE_NAME = 'Czech';
    const string CZECH_LANGUAGE_CODE = 'cs';
    const string AFRIKAANS_LANGUAGE_NAME = 'Afrikaans';
    const string AFRIKAANS_LANGUAGE_CODE = 'af';
    const string ARMENIAN_LANGUAGE_NAME = 'Armenian';
    const string ARMENIAN_LANGUAGE_CODE = 'hy';
    const string AFAR_LANGUAGE_NAME = 'Afar';
    const string AFAR_LANGUAGE_CODE = 'aa';
    const string BENGALI_LANGUAGE_NAME = 'Bengali';
    const string BENGALI_LANGUAGE_CODE = 'bn';

    const string LANGUAGE_NOT_FOUND = 'Language not found';
    public function __construct(
        protected LoggerInterface $logger,
        protected LanguageNormalizationService $languageNormalizationService,
        protected TransliterationDetectionService $transliterationDetectionService,
        protected FuzzySearchService $fuzzySearchService
    )
    {
    }

    public function process(string $languageInput, int $translitDetection): array
    {
        $uuid = Uuid::v4()->toRfc4122();

        if (empty($languageInput)) {
            $this->logger->warning(sprintf('[LanguageDetectionService][%s] Empty input', $uuid));
            return [
                'languageCode' => null,
                'input' => $languageInput,
                'count' => 0,
                'matches' => 0,
            ];
        }

        $this->logger->info(sprintf('[LanguageDetectionService][%s] Starting language detection', $uuid));

        $normalizedInput = $this->languageNormalizationService->normalizeText($languageInput);
        $words = explode(' ', $normalizedInput);
        $words = $this->languageNormalizationService->removeArticles($words);
        $count = count($words);

        if ($translitDetection) {
            $this->logger->info(sprintf('[LanguageDetectionService][%s] Running transliteration detection', $uuid));
            return $this->transliterationDetectionService->run($words, $uuid, microtime(true));
        }

        $languageCounts = [];
        $matchCount = 0;

        foreach ($words as $word) {
            $word = $this->languageNormalizationService->normalizeWord($word);
            if (!$word) {
                continue;
            }

            try {
                $matches = $this->fuzzySearchService->findClosestMatches($word, 5);

                foreach ($matches as $match) {
                    if (!isset($match['languageCode'])) {
                        continue;
                    }

                    $code = $match['languageCode'];
                    $languageCounts[$code] = ($languageCounts[$code] ?? 0) + 1;
                    $matchCount++;
                }
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    '[LanguageDetectionService][%s] Fuzzy search failed for "%s": %s',
                    $uuid,
                    $word,
                    $e->getMessage()
                ));
            }
        }

        $topLanguageCode = null;
        if (!empty($languageCounts)) {
            arsort($languageCounts);
            $topLanguageCode = array_key_first($languageCounts);
        }

        $this->logger->info(sprintf(
            '[LanguageDetectionService][%s] Completed detection. Detected language: %s (matches: %d)',
            $uuid,
            $topLanguageCode ?? 'none',
            $matchCount
        ));

        return [
            'languageCode' => $topLanguageCode,
            'input' => $languageInput,
            'count' => $count,
            'matches' => $matchCount,
        ];
    }

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
        ];
    }

    public static function getLanguageCodesForTransliteration(): array
    {
        return [
            self::LATVIAN_LANGUAGE_CODE,
            self::RUSSIAN_LANGUAGE_CODE,
        ];
    }

}