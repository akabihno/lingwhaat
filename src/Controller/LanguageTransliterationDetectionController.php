<?php

namespace App\Controller;

use App\Service\LanguageDetection\LanguageTransliteration\LanguageTransliterationDetectionService;
use OpenApi\Attributes as OA;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Language Detection')]
class LanguageTransliterationDetectionController extends AbstractController
{
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageTransliterationDetectionService $languageTransliterationDetectionService
    )
    {
    }

    #[Route('/api/translit', name: 'get_translit', methods: ['GET'])]
    #[OA\Get(
        path: '/api/translit',
        description: 'Detects the language of the provided text and returns its code, input, and match statistics. Assumes that input text is transliterated.',
        summary: 'Detect language from input text assuming transliteration',
        tags: ['Language Detection'],
        parameters: [
            new OA\Parameter(
                name: 'get_translit',
                description: 'The text to analyze for language detection',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    example: 'Араа лиист. Лаби, ка шодиен нэкур нау яайиет, седеешу маайас ар сиеву ун катьи'
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Language successfully detected',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'languageCode', description: 'Detected language code', type: 'string', example: 'lv'),
                        new OA\Property(property: 'input', description: 'Guess original text', type: 'string', example: 'Ārā līst. Labi, ka šodien nekur nav jāiet, sēdēšu mājās ar sievu un kaķi..'),
                        new OA\Property(property: 'count', description: 'Number of words in input', type: 'integer', example: 14),
                        new OA\Property(property: 'matches', description: 'Number of matches', type: 'integer', example: 14)
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - missing required parameter'
            ),
            new OA\Response(
                response: 429,
                description: 'Too many requests - rate limit exceeded'
            )
        ]
    )]
    public function run(Request $request, RateLimiterFactory $anonymousApiLimiter): Response
    {
        $clientIp = $request->getClientIp();
        $whitelistedIp = getenv('RATE_LIMITER_WHITELISTED_IP');

        $this->logger->info(
            'Validating IP address',
            ['client_ip' => $clientIp, 'whitelisted_ip' => $whitelistedIp, 'service' => '[LanguageDetectionController]']
        );

        if ($whitelistedIp && $whitelistedIp !== $clientIp) {
            $limiter = $anonymousApiLimiter->create($clientIp);

            if (false === $limiter->consume(1)->isAccepted()) {
                return new JsonResponse(
                    ['error' => 'Rate limit exceeded'],
                    Response::HTTP_TOO_MANY_REQUESTS
                );
            }
        }

        $inputText = $request->query->get('get_translit');
        if (!$inputText) {
            return new JsonResponse(
                ['error' => 'Missing required parameter: get_translit'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $result = $this->languageTransliterationDetectionService->process($inputText);

        return new JsonResponse($result, Response::HTTP_OK);
    }

}