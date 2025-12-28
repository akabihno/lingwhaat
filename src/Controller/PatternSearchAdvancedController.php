<?php

namespace App\Controller;

use App\Service\Search\PatternSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Search')]
class PatternSearchAdvancedController extends AbstractController
{
    public function __construct(
        protected PatternSearchService $patternSearchService,
    ) {
    }
    #[Route('/api/pattern-search-advanced', name: 'pattern_search_advanced', methods: ['POST'])]
    #[OA\Post(
        path: '/api/pattern-search-advanced',
        description: 'Regular mode searches for words matching pattern constraints. Intersection mode helps solve ciphers by finding words from multiple patterns that share common characters for cross-referencing alphabet mappings.',
        summary: 'Search for words matching advanced positional patterns or solve cipher/substitution puzzles by finding intersecting words'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'samePositions',
                    description: 'Array of position groups where positions must have the same character (1-indexed). For regular pattern search only.',
                    type: 'array',
                    items: new OA\Items(
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    ),
                    nullable: true
                ),
                new OA\Property(
                    property: 'fixedChars',
                    description: 'Object mapping positions to required characters (1-indexed). For regular pattern search only.',
                    type: 'object',
                    nullable: true
                ),
                new OA\Property(
                    property: 'exactLength',
                    description: 'Optional exact word length filter',
                    type: 'integer',
                    nullable: true
                ),
                new OA\Property(
                    property: 'languageCode',
                    description: 'Optional language code to filter results (e.g., en, ka, ru)',
                    type: 'string',
                    nullable: true
                ),
                new OA\Property(
                    property: 'intersections',
                    description: 'Array of pattern configurations for cipher-solving. Finds word combinations (one per pattern) from the same language that share at least 3 common characters. Use this OR samePositions/fixedChars, not both.',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(
                                property: 'samePositions',
                                type: 'array',
                                items: new OA\Items(type: 'array', items: new OA\Items(type: 'integer'))
                            ),
                            new OA\Property(
                                property: 'fixedChars',
                                type: 'object'
                            ),
                            new OA\Property(
                                property: 'exactLength',
                                type: 'integer',
                                nullable: true
                            ),
                            new OA\Property(
                                property: 'languageCode',
                                type: 'string',
                                nullable: true
                            ),
                        ],
                        type: 'object'
                    ),
                    nullable: true
                ),
                new OA\Property(
                    property: 'limit',
                    description: 'Maximum number of results to return',
                    type: 'integer',
                    default: 100,
                    maximum: 1000,
                    minimum: 1
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful pattern search or intersection search. For regular search: returns array of word objects. For intersection search: returns array of intersection group objects with languageCode and words array.',
        content: new OA\JsonContent(
            type: 'array',
            example: [
                ['word' => 'bobcat', 'ipa' => 'bɑbkæt', 'languageCode' => 'en']
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid parameters',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Either samePositions or fixedChars must be provided'),
            ],
            type: 'object'
        )
    )]
    public function search(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['error' => 'Invalid JSON payload: ' . json_last_error_msg()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $limit = (int) ($data['limit'] ?? 100);

        if ($limit < 1 || $limit > 1000) {
            return new JsonResponse(
                ['error' => 'Limit must be between 1 and 1000'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (isset($data['intersections']) && is_array($data['intersections'])) {
            return $this->handleIntersectionSearch($data['intersections'], $limit);
        }

        $samePositions = $data['samePositions'] ?? [];
        $fixedChars = $data['fixedChars'] ?? [];
        $exactLength = isset($data['exactLength']) ? (int) $data['exactLength'] : null;
        $languageCode = $data['languageCode'] ?? null;

        if (empty($samePositions) && empty($fixedChars)) {
            return new JsonResponse(
                ['error' => 'Either samePositions, fixedChars, or intersections must be provided'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_array($samePositions)) {
            return new JsonResponse(
                ['error' => 'samePositions must be an array'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_array($fixedChars)) {
            return new JsonResponse(
                ['error' => 'fixedChars must be an object/array'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($exactLength !== null && $exactLength < 1) {
            return new JsonResponse(
                ['error' => 'exactLength must be a positive integer'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $results = $this->patternSearchService->findByAdvancedPattern(
                $samePositions,
                $fixedChars,
                $exactLength,
                $languageCode,
                $limit
            );

            return new JsonResponse($results, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred during pattern search: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle intersection search request.
     *
     * @param array $intersections Array of pattern configurations
     * @param int $limit Maximum number of results
     * @return JsonResponse
     */
    private function handleIntersectionSearch(array $intersections, int $limit): JsonResponse
    {
        if (empty($intersections)) {
            return new JsonResponse(
                ['error' => 'intersections array cannot be empty'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (count($intersections) < 2) {
            return new JsonResponse(
                ['error' => 'At least 2 patterns are required for intersection search'],
                Response::HTTP_BAD_REQUEST
            );
        }

        foreach ($intersections as $index => $pattern) {
            if (!is_array($pattern)) {
                return new JsonResponse(
                    ['error' => "Pattern at index $index must be an array/object"],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $samePositions = $pattern['samePositions'] ?? [];
            $fixedChars = $pattern['fixedChars'] ?? [];

            if (empty($samePositions) && empty($fixedChars)) {
                return new JsonResponse(
                    ['error' => "Pattern at index $index must have either samePositions or fixedChars"],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (isset($pattern['exactLength']) && $pattern['exactLength'] < 1) {
                return new JsonResponse(
                    ['error' => "exactLength at pattern index $index must be a positive integer"],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        try {
            $results = $this->patternSearchService->findIntersections($intersections, $limit);

            return new JsonResponse($results, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred during intersection search: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}