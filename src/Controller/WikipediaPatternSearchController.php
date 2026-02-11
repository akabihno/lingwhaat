<?php

namespace App\Controller;

use App\Service\Search\WikipediaPatternSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use OpenApi\Attributes as OA;

class WikipediaPatternSearchController extends AbstractController
{
    public function __construct(
        protected WikipediaPatternSearchService $wikipediaPatternSearchService,
    ) {
    }

    #[Route('/api/wikipedia-pattern-search-sequence', name: 'wikipedia_pattern_search_sequence', methods: ['POST'])]
    #[OA\Post(
        path: '/api/wikipedia-pattern-search-sequence',
        description: 'Search for cipher patterns in Wikipedia articles using a global concatenated index. The search text must match the specified window size.',
        summary: 'Search for patterns in Wikipedia articles',
        tags: ['Pattern Search']
    )]
    #[OA\RequestBody(
        required: true,
        content: [
            'application/json' => new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['searchText', 'windowSize'],
                    properties: [
                        new OA\Property(
                            property: 'searchText',
                            description: 'Text to search for pattern matches',
                            type: 'string',
                            example: 'example text'
                        ),
                        new OA\Property(
                            property: 'windowSize',
                            description: 'Pattern window size (must match the length of normalized search text)',
                            type: 'integer',
                            example: 100
                        ),
                        new OA\Property(
                            property: 'limit',
                            description: 'Maximum number of results to return',
                            type: 'integer',
                            default: 50,
                            maximum: 1000,
                            minimum: 1
                        ),
                    ],
                    type: 'object'
                )
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful pattern search',
        content: [
            'application/json' => new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'global_position', type: 'integer', example: 12345),
                            new OA\Property(property: 'article_id', type: 'integer', example: 678),
                            new OA\Property(property: 'local_position', type: 'integer', example: 100),
                            new OA\Property(property: 'pattern', type: 'string', example: '0,1,2,3,4'),
                            new OA\Property(property: 'length', type: 'integer', example: 100),
                            new OA\Property(property: 'pattern_hash', type: 'integer', example: 123456789),
                        ],
                        type: 'object'
                    )
                )
            )
        ]
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid parameters',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'searchText is required'),
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

        $searchText = $data['searchText'] ?? null;
        $windowSize = isset($data['windowSize']) ? (int) $data['windowSize'] : null;
        $limit = (int) ($data['limit'] ?? 50);

        if (empty($searchText)) {
            return new JsonResponse(
                ['error' => 'searchText is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($windowSize === null) {
            return new JsonResponse(
                ['error' => 'windowSize is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($windowSize <= 0) {
            return new JsonResponse(
                ['error' => 'windowSize must be greater than 0'],
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
            $results = $this->wikipediaPatternSearchService->search($searchText, $limit, $windowSize);

            return new JsonResponse($results, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred during pattern search: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
