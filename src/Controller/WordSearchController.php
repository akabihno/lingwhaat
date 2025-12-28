<?php

namespace App\Controller;

use App\Service\Search\FuzzySearchService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Word Search')]
class WordSearchController extends AbstractController
{
    public function __construct(
        protected FuzzySearchService $fuzzySearchService
    ) {
    }

    #[Route('/api/search', name: 'search_word', methods: ['GET'])]
    #[OA\Get(
        path: '/api/search',
        description: 'Searches for the closest matches to a given word (supports fuzzy matching to handle typos or misspellings).',
        summary: 'Find approximate word matches across languages',
        parameters: [
            new OA\Parameter(
                name: 'word',
                description: 'The input word to search for (exact or approximate match)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'hprse')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Maximum number of results to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 5, example: 5)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of closest word matches found in Elasticsearch',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'word', description: 'Matched word', type: 'string', example: 'horse'),
                            new OA\Property(property: 'ipa', description: 'IPA pronunciation', type: 'string', example: 'hɔːs'),
                            new OA\Property(property: 'languageCode', description: 'Language code of the word', type: 'string', example: 'en')
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Bad request — missing or invalid parameters'),
            new OA\Response(response: 500, description: 'Internal server error')
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $word = $request->query->get('word');
        $limit = (int) $request->query->get('limit', 5);

        if (empty($word)) {
            return new JsonResponse(
                ['error' => 'Missing required parameter: word'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $matches = $this->fuzzySearchService->findClosestMatches($word, $limit);
            return $this->json($matches, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Search failed', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
