<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Constant\LanguageServicesAndCodes;
use App\Service\LanguageDetection\LanguageDetectionService;
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
use App\Service\LanguageDetection\LanguageTransliteration\Command\UseIpaPredictorModelCommand;
use App\Service\LanguageDetection\LanguageTransliteration\Command\UseWordPredictorModelCommand;
use App\Service\LanguageNormalizationService;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TransliterationDetectionService
{
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageNormalizationService $languageNormalizationService,
        protected UseIpaPredictorModelCommand $ipaPredictorModelCommand,
        protected UseWordPredictorModelCommand $wordPredictorModelCommand,
        protected UseIpaPredictorModelService $ipaPredictorModelService,
        protected UseWordPredictorModelService $wordPredictorModelService,
        protected FrenchLanguageService $frenchLanguageService,
        protected GermanLanguageService $germanLanguageService,
        protected GreekLanguageService $greekLanguageService,
        protected ItalianLanguageService $italianLanguageService,
        protected LatvianLanguageService $latvianLanguageService,
        protected LithuanianLanguageService $lithuanianLanguageService,
        protected PolishLanguageService $polishLanguageService,
        protected PortugueseLanguageService $portugueseLanguageService,
        protected RomanianLanguageService $romanianLanguageService,
        protected RussianLanguageService $russianLanguageService,
        protected SerboCroatianLanguageService $serboCroatianLanguageService,
        protected TagalogLanguageService $tagalogLanguageService,
        protected UkrainianLanguageService $ukrainianLanguageService,
        protected SpanishLanguageService $spanishLanguageService,
        protected LatinLanguageService $latinLanguageService,
        protected SwedishLanguageService $swedishLanguageService,
        protected EstonianLanguageService $estonianLanguageService,
        protected EnglishLanguageService $englishLanguageService,
        protected DutchLanguageService $dutchLanguageService,
        protected HindiLanguageService $hindiLanguageService,
        protected GeorgianLanguageService $georgianLanguageService,
        protected TurkishLanguageService $turkishLanguageService,
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function run(array $words, string $uuidStr, float $start): array
    {
        foreach ($words as $word) {
            $wordResults = [];
            $languageCodes = LanguageDetectionService::getLanguageCodesForTransliteration();

            foreach ($languageCodes as $srcLanguageCode) {
                $wordPredictedIpa = $this->ipaPredictorModelService->run($srcLanguageCode, $word);

                foreach ($languageCodes as $dstLanguageCode) {
                    if ($dstLanguageCode != $srcLanguageCode) {
                        $predictedTargetWord = $this->wordPredictorModelService->run($dstLanguageCode, $wordPredictedIpa);
                        $success = $this->checkLanguage($dstLanguageCode, $predictedTargetWord);
                        $wordResults[$srcLanguageCode][$dstLanguageCode] = $success;

                        if (!isset($destinationLanguageScores[$dstLanguageCode])) {
                            $destinationLanguageScores[$dstLanguageCode] = 0;
                        }
                        if ($success) {
                            $destinationLanguageScores[$dstLanguageCode]++;
                        }
                    }

                }
            }

            $languageCounts[$word] = $wordResults;
            $this->logLanguageDetectionResult($uuidStr, [$word => $wordResults]);
        }

        arsort($destinationLanguageScores);
        $mostDetectedLanguage = array_key_first($destinationLanguageScores);

        $finish = microtime(true);

        return [
            'language' => 'Test',
            'code' => $mostDetectedLanguage,
            'input' => 'input',
            'count' => 0,
            'matches' => 0,
            'time' => $finish - $start
        ];

    }

    protected function logLanguageDetectionResult(string $uuidStr, array $result): void
    {
        $this->logger->info(
            json_encode($result),
            ['uuid' => $uuidStr, 'service' => '[TransliterationDetectionService]']
        );
    }

    protected function checkLanguage(string $languageCode, string $word): bool
    {
        switch ($languageCode) {
            case LanguageServicesAndCodes::FRENCH_LANGUAGE_CODE:
                return $this->checkFrenchLanguage($word);
            case LanguageServicesAndCodes::GERMAN_LANGUAGE_CODE:
                return $this->checkGermanLanguage($word);
            case LanguageServicesAndCodes::GREEK_LANGUAGE_CODE:
                return $this->checkGreekLanguage($word);
            case LanguageServicesAndCodes::ITALIAN_LANGUAGE_CODE:
                return $this->checkItalianLanguage($word);
            case LanguageServicesAndCodes::LATVIAN_LANGUAGE_CODE:
                return $this->checkLatvianLanguage($word);
            case LanguageServicesAndCodes::LITHUANIAN_LANGUAGE_CODE:
                return $this->checkLithuanianLanguage($word);
            case LanguageServicesAndCodes::POLISH_LANGUAGE_CODE:
                return $this->checkPolishLanguage($word);
            case LanguageServicesAndCodes::PORTUGUESE_LANGUAGE_CODE:
                return $this->checkPortugueseLanguage($word);
            case LanguageServicesAndCodes::ROMANIAN_LANGUAGE_CODE:
                return $this->checkRomanianLanguage($word);
            case LanguageServicesAndCodes::RUSSIAN_LANGUAGE_CODE:
                return $this->checkRussianLanguage($word);
            case LanguageServicesAndCodes::SERBOCROATIAN_LANGUAGE_CODE:
                return $this->checkSerboCroatianLanguage($word);
            case LanguageServicesAndCodes::TAGALOG_LANGUAGE_CODE:
                return $this->checkTagalogLanguage($word);
            case LanguageServicesAndCodes::UKRAINIAN_LANGUAGE_CODE:
                return $this->checkUkrainianLanguage($word);
            case LanguageServicesAndCodes::SPANISH_LANGUAGE_CODE:
                return $this->checkSpanishLanguage($word);
            case LanguageServicesAndCodes::LATIN_LANGUAGE_CODE:
                return $this->checkLatinLanguage($word);
            case LanguageServicesAndCodes::SWEDISH_LANGUAGE_CODE:
                return $this->checkSwedishLanguage($word);
            case LanguageServicesAndCodes::ESTONIAN_LANGUAGE_CODE:
                return $this->checkEstonianLanguage($word);
            case LanguageServicesAndCodes::ENGLISH_LANGUAGE_CODE:
                return $this->checkEnglishLanguage($word);
            case LanguageServicesAndCodes::DUTCH_LANGUAGE_CODE:
                return $this->checkDutchLanguage($word);
            case LanguageServicesAndCodes::HINDI_LANGUAGE_CODE:
                return $this->checkHindiLanguage($word);
            case LanguageServicesAndCodes::GEORGIAN_LANGUAGE_CODE:
                return $this->checkGeorgianLanguage($word);
            case LanguageServicesAndCodes::TURKISH_LANGUAGE_CODE:
                return $this->checkTurkishLanguage($word);
        }

        return false;
    }

    protected function checkFrenchLanguage(string $word): bool
    {
        return $this->frenchLanguageService->findApproximateByName($word);
    }

    protected function checkGermanLanguage(string $word): bool
    {
        return $this->germanLanguageService->findApproximateByName($word);
    }

    protected function checkGreekLanguage(string $word): bool
    {
        return $this->greekLanguageService->findApproximateByName($word);
    }

    protected function checkItalianLanguage(string $word): bool
    {
        return $this->italianLanguageService->findApproximateByName($word);
    }

    protected function checkLatvianLanguage(string $word): bool
    {
        return $this->latvianLanguageService->findApproximateByName($word);
    }

    protected function checkLithuanianLanguage(string $word): bool
    {
        return $this->lithuanianLanguageService->findApproximateByName($word);
    }

    protected function checkPolishLanguage(string $word): bool
    {
        return $this->polishLanguageService->findApproximateByName($word);
    }

    protected function checkPortugueseLanguage(string $word): bool
    {
        return $this->portugueseLanguageService->findApproximateByName($word);
    }

    protected function checkRomanianLanguage(string $word): bool
    {
        return $this->romanianLanguageService->findApproximateByName($word);
    }

    protected function checkRussianLanguage(string $word): bool
    {
        return $this->russianLanguageService->findApproximateByName($word);
    }

    protected function checkSerboCroatianLanguage(string $word): bool
    {
        return $this->serboCroatianLanguageService->findApproximateByName($word);
    }

    protected function checkTagalogLanguage(string $word): bool
    {
        return $this->tagalogLanguageService->findApproximateByName($word);
    }

    protected function checkUkrainianLanguage(string $word): bool
    {
        return $this->ukrainianLanguageService->findApproximateByName($word);
    }

    protected function checkSpanishLanguage(string $word): bool
    {
        return $this->spanishLanguageService->findApproximateByName($word);
    }

    protected function checkLatinLanguage(string $word): bool
    {
        return $this->latinLanguageService->findApproximateByName($word);
    }

    protected function checkSwedishLanguage(string $word): bool
    {
        return $this->swedishLanguageService->findApproximateByName($word);
    }

    protected function checkEstonianLanguage(string $word): bool
    {
        return $this->estonianLanguageService->findApproximateByName($word);
    }

    protected function checkEnglishLanguage(string $word): bool
    {
        return $this->englishLanguageService->findApproximateByName($word);
    }

    protected function checkDutchLanguage(string $word): bool
    {
        return $this->dutchLanguageService->findApproximateByName($word);
    }

    protected function checkHindiLanguage(string $word): bool
    {
        return $this->hindiLanguageService->findApproximateByName($word);
    }

    protected function checkGeorgianLanguage(string $word): bool
    {
        return $this->georgianLanguageService->findApproximateByName($word);
    }

    protected function checkTurkishLanguage(string $word): bool
    {
        return $this->turkishLanguageService->findApproximateByName($word);
    }

}