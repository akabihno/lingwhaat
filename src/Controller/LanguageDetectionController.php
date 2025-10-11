<?php

namespace App\Controller;
use App\Service\LanguageDetection\LanguageDetectionService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;


#[OA\Tag(name: 'Language Detection')]
class LanguageDetectionController extends AbstractController
{
    public function __construct(protected LanguageDetectionService $languageDetectionService)
    {
    }

    #[Route('/api/language', name: 'get_language', methods: ['GET'])]
    #[OA\Get(
        path: '/api/language',
        description: 'Detects the language of the provided text and returns language name and code',
        summary: 'Detect language from input text',
        tags: ['Language Detection'],
        parameters: [
            new OA\Parameter(
                name: 'get_language',
                description: 'The text to analyze for language detection',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'Hello, how are you?')
            ),
            new OA\Parameter(
                name: 'translit_detection',
                description: 'Enable transliteration detection (0 or 1)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0, example: 0)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Language successfully detected',
                content: new OA\JsonContent(
                    properties: [
                        'language' => new OA\Property(type: 'string', example: 'English'),
                        'code' => new OA\Property(type: 'string', example: 'en'),
                        'input' => new OA\Property(type: 'string', example: 'Hello, how are you?'),
                        'time' => new OA\Property(type: 'float', example: 0.125),
                        'count' => new OA\Property(type: 'integer', example: 4),
                        'matches' => new OA\Property(type: 'array', items: new OA\Items(type: 'string'))
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - missing required parameter',
                content: new OA\JsonContent(
                    properties: [
                        'error' => new OA\Property(type: 'string', example: 'Missing required parameter: get_language')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 429,
                description: 'Too many requests',
                content: new OA\JsonContent(
                    properties: [
                        'error' => new OA\Property(type: 'string', example: 'Rate limit exceeded')
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    public function run(Request $request, RateLimiterFactory $anonymousApiLimiter): Response
    {
        $limiter = $anonymousApiLimiter->create($request->getClientIp());

        if (false === $limiter->consume(1)->isAccepted()) {
            return new JsonResponse(
                ['error' => 'Rate limit exceeded'],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        $inputText = $request->query->get('get_language');
        if (!$inputText) {
            return new JsonResponse(
                ['error' => 'Missing required parameter: get_language'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $translitDetection = $request->query->getInt('translit_detection', 0);

        $languageAndCode = $this->languageDetectionService->process(
            $inputText,
            $translitDetection
        );

        return new JsonResponse($languageAndCode, Response::HTTP_OK);
    }
}