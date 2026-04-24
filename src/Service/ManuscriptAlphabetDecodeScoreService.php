<?php

namespace App\Service;

use App\Entity\ManuscriptAlphabetDecodeResultEntity;
use App\Service\LanguageDetection\LanguageValidation\LanguageVerificationService;

class ManuscriptAlphabetDecodeScoreService
{
    public function __construct(
        private readonly LanguageVerificationService $verificationService,
    ) {
    }

    /**
     * @return array{language_code: string, language_score: float}
     */
    public function score(ManuscriptAlphabetDecodeResultEntity $result): array
    {
        $languageCode = $result->getLanguageCode();
        $phrase = $result->getDecodedPhrase();

        $verification = $this->verificationService->verifyLanguage($phrase, $languageCode, 1);
        $score = (float)($verification['matchPercentage'] ?? 0.0);

        return ['language_code' => $languageCode, 'language_score' => $score];
    }
}
