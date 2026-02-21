<?php

namespace App\Controller;

use App\Service\LanguageDetection\LanguageValidation\LanguageVerificationService;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class LanguageVerificationController extends AbstractController
{
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageVerificationService $languageVerificationService
    )
    {
    }

    #[Route('/api/language/verify', name: 'language_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['text']) || !isset($data['languageCode'])) {
            return $this->json([
                'error' => 'Both "text" and "languageCode" are required'
            ], 400);
        }

        $text = $data['text'];
        $languageCode = $data['languageCode'];
        $minNgram = $data['minNgram'] ?? 3;
        $maxNgram = $data['maxNgram'] ?? 5;
        $fuzziness = $data['fuzziness'] ?? 1;

        try {
            $result = $this->languageVerificationService->verifyLanguage(
                $text,
                $languageCode,
                $minNgram,
                $maxNgram,
                $fuzziness
            );

            $this->logger->info(
                'Language verification request processed',
                [
                    'controller' => '[LanguageVerificationController]',
                    'languageCode' => $languageCode,
                    'textLength' => mb_strlen($text),
                    'matchPercentage' => $result['matchPercentage']
                ]
            );

            return $this->json($result);
        } catch (\Exception $e) {
            $this->logger->error(
                'Language verification failed',
                [
                    'controller' => '[LanguageVerificationController]',
                    'error' => $e->getMessage(),
                    'languageCode' => $languageCode
                ]
            );

            return $this->json([
                'error' => 'Language verification failed: ' . $e->getMessage()
            ], 500);
        }
    }
}