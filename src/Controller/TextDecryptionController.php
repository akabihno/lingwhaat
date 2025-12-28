<?php

namespace App\Controller;

use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\TextDecryptionService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[OA\Tag(name: 'Text Decryption')]
class TextDecryptionController extends AbstractController
{
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected TextDecryptionService $decryptionService
    ) {
    }

    #[Route('/api/decrypt', name: 'decrypt_text', methods: ['POST'])]
    #[OA\Post(
        path: '/api/decrypt',
        description: 'Attempts to decrypt text by trying different letter substitutions and question mark replacements, then validates words against Elasticsearch.',
        summary: 'Decrypt text using letter substitution and fuzzy matching',
        requestBody: new OA\RequestBody(
            description: 'Text decryption parameters',
            required: true,
            content: new OA\JsonContent(
                required: ['text', 'languageCode'],
                properties: [
                    new OA\Property(
                        property: 'text',
                        description: 'The encrypted or corrupted text to decrypt.',
                        type: 'string',
                        example: 'geel desdem ecolevol dem vomica'
                    ),
                    new OA\Property(
                        property: 'languageCode',
                        description: 'Target language code to validate against (e.g., "odt" for Old Dutch, "en" for English)',
                        type: 'string',
                        example: 'odt'
                    ),
                    new OA\Property(
                        property: 'minCount',
                        description: 'Minimum number of words that must match for a successful decryption',
                        type: 'integer',
                        default: 5,
                        example: 5
                    )
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Decryption attempt completed (check "success" field for result)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'success',
                            description: 'Whether decryption met the minimum match threshold',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'original_text',
                            description: 'The original input text',
                            type: 'string',
                            example: 'geel desdem ecolevol dem vomica'
                        ),
                        new OA\Property(
                            property: 'decrypted_text',
                            description: 'The best decrypted version found',
                            type: 'string',
                            example: 'ghel gestem ecolevol tem vomica'
                        ),
                        new OA\Property(
                            property: 'match_count',
                            description: 'Number of words successfully matched in Elasticsearch',
                            type: 'integer',
                            example: 12
                        ),
                        new OA\Property(
                            property: 'min_count',
                            description: 'The minimum count required for success',
                            type: 'integer',
                            example: 5
                        ),
                        new OA\Property(
                            property: 'matched_words',
                            description: 'List of words that were found in the language database',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['ghel', 'gestem', 'tem', 'vomica']
                        ),
                        new OA\Property(
                            property: 'substitutions',
                            description: 'Letter substitution pattern applied (from => to)',
                            type: 'object',
                            example: ['d' => 't', 'e' => 'a']
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - missing required parameters'
            ),
            new OA\Response(
                response: 429,
                description: 'Too many requests - rate limit exceeded'
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error during decryption'
            )
        ]
    )]
    public function decrypt(Request $request, RateLimiterFactory $anonymousApiLimiter): Response
    {
        $clientIp = $request->getClientIp();
        $whitelistedIp = getenv('RATE_LIMITER_WHITELISTED_IP');

        $this->logger->info(
            'Text decryption request received',
            ['client_ip' => $clientIp, 'method' => $request->getMethod(), 'service' => '[TextDecryptionController]']
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

        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $text = $data['text'] ?? null;
            $languageCode = $data['languageCode'] ?? null;
            $minCount = (int) ($data['minCount'] ?? 5);
        } else {
            $text = $request->query->get('text');
            $languageCode = $request->query->get('languageCode');
            $minCount = (int) $request->query->get('minCount', 5);
        }

        if (empty($text)) {
            return new JsonResponse(
                ['error' => 'Missing required parameter: text'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (empty($languageCode)) {
            return new JsonResponse(
                ['error' => 'Missing required parameter: languageCode'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($minCount < 0) {
            return new JsonResponse(
                ['error' => 'Invalid parameter: minCount must be non-negative'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->logger->info(
                'Starting text decryption',
                [
                    'language_code' => $languageCode,
                    'text_length' => strlen($text),
                    'min_count' => $minCount,
                    'service' => '[TextDecryptionController]'
                ]
            );

            $result = $this->decryptionService->decryptText($text, $languageCode, $minCount);

            $this->logger->info(
                'Text decryption completed',
                [
                    'success' => $result['success'],
                    'match_count' => $result['match_count'],
                    'substitutions_used' => !empty($result['substitutions']),
                    'service' => '[TextDecryptionController]'
                ]
            );

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Text decryption failed',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'service' => '[TextDecryptionController]'
                ]
            );

            return new JsonResponse(
                [
                    'error' => 'Decryption failed',
                    'details' => $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
