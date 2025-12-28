<?php

namespace App\Controller;

use App\Service\LanguageDetection\LanguageValidation\LanguageValidationService;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[OA\Tag(name: 'Language Validation')]
class LanguageValidationController extends AbstractController
{
    public function __construct(
        protected LoggerInterface $logger,
        protected LanguageValidationService $languageValidationService
    )
    {
    }

    #[Route('/api/validate', name: 'validate_language', methods: ['GET'])]
    #[OA\Get(
        path: '/api/validate',
        description: 'Analyzes text to determine if it looks like natural language by checking vowel-consonant patterns, cluster formations, and linguistic characteristics.',
        summary: 'Check if text looks like natural language',
        parameters: [
            new OA\Parameter(
                name: 'text',
                description: 'The text to analyze for natural language patterns',
                in: 'query',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    example: 'The quick brown fox jumps over the lazy dog'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Text successfully analyzed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'score',
                            description: 'Natural language score from 0 to 100',
                            type: 'number',
                            format: 'float',
                            example: 85.5
                        ),
                        new OA\Property(
                            property: 'isNatural',
                            description: 'Whether the text appears to be natural language (score >= 60)',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'details',
                            description: 'Detailed breakdown of analysis scores',
                            properties: [
                                new OA\Property(
                                    property: 'vowelRatio',
                                    description: 'Score based on vowel to consonant ratio (0-100)',
                                    type: 'number',
                                    format: 'float',
                                    example: 90.0
                                ),
                                new OA\Property(
                                    property: 'consonantClusters',
                                    description: 'Score based on consonant cluster analysis (0-100)',
                                    type: 'number',
                                    format: 'float',
                                    example: 100.0
                                ),
                                new OA\Property(
                                    property: 'vowelClusters',
                                    description: 'Score based on vowel cluster analysis (0-100)',
                                    type: 'number',
                                    format: 'float',
                                    example: 85.0
                                ),
                                new OA\Property(
                                    property: 'alternationPattern',
                                    description: 'Score based on vowel-consonant alternation (0-100)',
                                    type: 'number',
                                    format: 'float',
                                    example: 75.0
                                ),
                                new OA\Property(
                                    property: 'vowelCount',
                                    description: 'Number of vowels in the text',
                                    type: 'integer',
                                    example: 12
                                ),
                                new OA\Property(
                                    property: 'consonantCount',
                                    description: 'Number of consonants in the text',
                                    type: 'integer',
                                    example: 18
                                ),
                                new OA\Property(
                                    property: 'totalLetters',
                                    description: 'Total number of letters analyzed',
                                    type: 'integer',
                                    example: 30
                                )
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - missing required parameter or text too short'
            ),
            new OA\Response(
                response: 429,
                description: 'Too many requests - rate limit exceeded'
            )
        ]
    )]
    public function check(Request $request, RateLimiterFactory $anonymousApiLimiter): JsonResponse
    {
        $clientIp = $request->getClientIp();
        $whitelistedIp = getenv('RATE_LIMITER_WHITELISTED_IP');

        $this->logger->info(
            'Validating IP address',
            ['client_ip' => $clientIp, 'whitelisted_ip' => $whitelistedIp, 'service' => '[LanguageValidationController]']
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

        $text = $request->query->get('text');
        if (!$text) {
            return new JsonResponse(
                ['error' => 'Missing required parameter: text'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $result = $this->languageValidationService->analyze($text);

        if ($result['score'] === 0 && isset($result['details']['error'])) {
            return new JsonResponse(
                ['error' => $result['details']['error']],
                Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

}