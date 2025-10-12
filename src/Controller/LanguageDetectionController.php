<?php

namespace App\Controller;
use App\Service\LanguageDetection\LanguageDetectionService;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
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
        protected LoggerInterface $logger,
        protected LanguageDetectionService $languageDetectionService
    )
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
                schema: new OA\Schema(type: 'string', example: 'Lorem ipsum dolor sit amet, 
                consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et 
                dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco 
                laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit 
                in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat 
                cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.')
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
                        new OA\Property(property: 'language', description: 'Detected language name', type: 'string', example: 'Latin'),
                        new OA\Property(property: 'code', description: 'Language code', type: 'string', example: 'la'),
                        new OA\Property(property: 'input', description: 'Original input text', type: 'string', example: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'),
                        new OA\Property(property: 'count', description: 'Number of words', type: 'integer', example: 51),
                        new OA\Property(property: 'matches', description: 'Matched words count', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'time', description: 'Processing time in seconds', type: 'number', example: 2.8751020431518555)
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
        $this->logger->info(sprintf('[LanguageDetectionController] client IP: %s, whitelisted IP: %s', $clientIp, $whitelistedIp));
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

        $languageAndCode = $this->languageDetectionService->process(
            $inputText,
            $translitDetection
        );

        return new JsonResponse($languageAndCode, Response::HTTP_OK);
    }
}