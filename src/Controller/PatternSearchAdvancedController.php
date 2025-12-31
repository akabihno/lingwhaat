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
        content: [
            'application/json' => new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['limit'],
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
                                    new OA\Property(property: 'samePositions', type: 'array', items: new OA\Items(type: 'array', items: new OA\Items(type: 'integer'))),
                                    new OA\Property(property: 'fixedChars', type: 'object'),
                                    new OA\Property(property: 'exactLength', type: 'integer', nullable: true),
                                    new OA\Property(property: 'languageCode', type: 'string', nullable: true),
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
                ),
                examples: [
                    new OA\Examples(
                        example: 'Regular Pattern Search',
                        summary: 'Regular Pattern Search',
                        value: [
                            'samePositions' => [[1, 3, 6]],
                            'fixedChars' => ['2' => 'o'],
                            'exactLength' => 7,
                            'languageCode' => 'en',
                            'limit' => 100
                        ]
                    ),
                    new OA\Examples(
                        example: 'Intersection Search',
                        summary: 'Cipher-Solving Intersection Search',
                        value: [
                            'intersections' => [
                                [
                                    'samePositions' => [[1, 3, 6], [2, 4, 8]],
                                    'fixedChars' => ['2' => 'o'],
                                    'exactLength' => 8
                                ],
                                [
                                    'samePositions' => [[1, 7]],
                                    'exactLength' => 7
                                ]
                            ],
                            'limit' => 100
                        ]
                    )
                ]
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful pattern search or intersection search. For regular search: returns array of word objects. For intersection search: returns array of intersection group objects with languageCode and words array.',
        content: [
            'application/json' => new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'object')),
                examples: [
                    new OA\Examples(
                        example: 'Regular Search Result',
                        summary: 'Regular Pattern Search Result',
                        value: [
                            ['word' => 'pattern', 'ipa' => 'ˈpætərn', 'languageCode' => 'en'],
                            ['word' => 'battery', 'ipa' => 'ˈbætəri', 'languageCode' => 'en']
                        ]
                    ),
                    new OA\Examples(
                        example: 'Intersection Search Result',
                        summary: 'Intersection Search Result',
                        value: [
                            [
                                'languageCode' => 'en',
                                'words' => [
                                    ['word' => 'pattern', 'ipa' => 'ˈpætərn'],
                                    ['word' => 'battery', 'ipa' => 'ˈbætəri']
                                ]
                            ]
                        ]
                    )
                ]
            )
        ]
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

    #[Route('/api/pattern-search-sequence', name: 'pattern_search_sequence', methods: ['POST'])]
    #[OA\Post(
        path: '/api/pattern-search-sequence',
        description: 'Search for word sequences where a specific letter appears exclusively at given positions across multiple words in the same language. For example, [[1,4], [3], [9]] means a letter appears at positions 1,4 in the first word, position 3 in the second word, and position 9 in the third word.',
        summary: 'Search for word sequences with positional letter patterns across multiple words'
    )]
    #[OA\RequestBody(
        required: true,
        content: [
            'application/json' => new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['sequencePositions', 'limit'],
                    properties: [
                        new OA\Property(
                            property: 'sequencePositions',
                            description: 'Array of position arrays for each word in the sequence. Each sub-array contains positions (1-indexed) where a specific letter appears exclusively in that word.',
                            type: 'array',
                            items: new OA\Items(
                                type: 'array',
                                items: new OA\Items(type: 'integer')
                            ),
                            example: [[1, 4], [3], [9]]
                        ),
                        new OA\Property(
                            property: 'languageCode',
                            description: 'Optional language code to filter results (e.g., en, ka, ru)',
                            type: 'string',
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
                ),
                examples: [
                    new OA\Examples(
                        example: 'Sequence Pattern Search',
                        summary: 'Find sequences where a letter appears at specific positions',
                        value: [
                            'sequencePositions' => [[1, 4], [3], [9]],
                            'languageCode' => 'en',
                            'limit' => 100
                        ]
                    )
                ]
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful sequence pattern search. Returns array of result groups ordered by language code.',
        content: [
            'application/json' => new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'object')),
                examples: [
                    new OA\Examples(
                        example: 'Sequence Search Result',
                        summary: 'Sequence Pattern Search Result',
                        value: [
                            [
                                'languageCode' => 'en',
                                'sequences' => [
                                    [
                                        'languageCode' => 'en',
                                        'letter' => 'a',
                                        'words' => [
                                            ['word' => 'that', 'ipa' => 'ðæt'],
                                            ['word' => 'car', 'ipa' => 'kɑr'],
                                            ['word' => 'something', 'ipa' => 'ˈsʌmθɪŋ']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    )
                ]
            )
        ]
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid parameters',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'sequencePositions must be provided and must be an array'),
            ],
            type: 'object'
        )
    )]
    public function searchSequence(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['error' => 'Invalid JSON payload: ' . json_last_error_msg()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $sequencePositions = $data['sequencePositions'] ?? null;
        $languageCode = $data['languageCode'] ?? null;
        $limit = (int) ($data['limit'] ?? 100);

        if ($limit < 1 || $limit > 1000) {
            return new JsonResponse(
                ['error' => 'Limit must be between 1 and 1000'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($sequencePositions === null || !is_array($sequencePositions)) {
            return new JsonResponse(
                ['error' => 'sequencePositions must be provided and must be an array'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (empty($sequencePositions)) {
            return new JsonResponse(
                ['error' => 'sequencePositions cannot be empty'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validate that each element is an array of positions
        foreach ($sequencePositions as $index => $positions) {
            if (!is_array($positions)) {
                return new JsonResponse(
                    ['error' => "Position array at index $index must be an array"],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (empty($positions)) {
                return new JsonResponse(
                    ['error' => "Position array at index $index cannot be empty"],
                    Response::HTTP_BAD_REQUEST
                );
            }

            foreach ($positions as $position) {
                if (!is_int($position) || $position < 1) {
                    return new JsonResponse(
                        ['error' => "All positions must be positive integers (at index $index)"],
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
        }

        try {
            $results = $this->patternSearchService->findBySequencePattern(
                $sequencePositions,
                $languageCode,
                $limit
            );

            return new JsonResponse($results, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred during sequence pattern search: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}