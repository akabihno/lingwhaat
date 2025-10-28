<?php

namespace App\Controller;

use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\Logging\ElasticsearchLogger;
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
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageDetectionService $languageDetectionService
    )
    {
    }

    #[Route('/api/language', name: 'get_language', methods: ['GET'])]
    #[OA\Get(
        path: '/api/language',
        description: 'Detects the language of the provided text and returns its code, input, and match statistics.',
        summary: 'Detect language from input text',
        tags: ['Language Detection'],
        parameters: [
            new OA\Parameter(
                name: 'get_language',
                description: 'The text to analyze for language detection',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    example: 'Sed ut perspiciatis, unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa, quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt, explicabo.'
                )
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
                        new OA\Property(property: 'languageCode', description: 'Detected language code', type: 'string', example: 'la'),
                        new OA\Property(property: 'input', description: 'Original input text', type: 'string', example: 'Sed ut perspiciatis, unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam eaque ipsa, quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt, explicabo.'),
                        new OA\Property(property: 'count', description: 'Number of words in input', type: 'integer', example: 25),
                        new OA\Property(property: 'matches', description: 'Number of matches (including similarity search)', type: 'integer', example: 85)
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

        $inputText = $request->query->get('get_language');
        if (!$inputText) {
            return new JsonResponse(
                ['error' => 'Missing required parameter: get_language'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $translitDetection = $request->query->getInt('translit_detection', 0);

        $result = $this->languageDetectionService->process(
            $inputText,
            $translitDetection
        );

        return new JsonResponse($result, Response::HTTP_OK);
    }
}