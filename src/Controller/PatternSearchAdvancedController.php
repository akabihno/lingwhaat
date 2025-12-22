<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use OpenApi\Attributes as OA;

class PatternSearchAdvancedController extends PatternSearchController
{
    #[Route('/api/pattern-search-advanced', name: 'pattern_search_advanced', methods: ['POST'])]
    #[OA\Post(
        path: '/api/pattern-search-advanced',
        summary: 'Search for words matching advanced positional patterns',
        tags: ['Search']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'samePositions',
                    description: 'Array of position groups where positions must have the same character (1-indexed)',
                    type: 'array',
                    items: new OA\Items(
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [1, 3, 6]
                    ),
                    example: [[1, 3, 6], [2, 4]]
                ),
                new OA\Property(
                    property: 'fixedChars',
                    description: 'Object mapping positions to required characters (1-indexed)',
                    type: 'object',
                    example: ['2' => 'o', '5' => 'x']
                ),
                new OA\Property(
                    property: 'exactLength',
                    description: 'Optional exact word length filter',
                    type: 'integer',
                    example: 5,
                    nullable: true
                ),
                new OA\Property(
                    property: 'languageCode',
                    description: 'Optional language code to filter results (e.g., en, ka, ru)',
                    type: 'string',
                    example: 'en',
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
            type: 'object',
            example: [
                'samePositions' => [[1, 3, 6]],
                'fixedChars' => ['2' => 'o'],
                'exactLength' => 7,
                'languageCode' => 'en',
                'limit' => 100
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful pattern search',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'word', type: 'string', example: 'bobcat'),
                    new OA\Property(property: 'ipa', type: 'string', example: 'bɑbkæt'),
                    new OA\Property(property: 'languageCode', type: 'string', example: 'en'),
                ],
                type: 'object'
            )
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

        $samePositions = $data['samePositions'] ?? [];
        $fixedChars = $data['fixedChars'] ?? [];
        $exactLength = isset($data['exactLength']) ? (int) $data['exactLength'] : null;
        $languageCode = $data['languageCode'] ?? null;
        $limit = (int) ($data['limit'] ?? 100);

        if (empty($samePositions) && empty($fixedChars)) {
            return new JsonResponse(
                ['error' => 'Either samePositions or fixedChars must be provided'],
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

        if ($limit < 1 || $limit > 1000) {
            return new JsonResponse(
                ['error' => 'Limit must be between 1 and 1000'],
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

}