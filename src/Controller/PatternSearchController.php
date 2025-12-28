<?php

namespace App\Controller;

use App\Service\Search\PatternSearchService;
use Exception;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PatternSearchController extends AbstractController
{
    public function __construct(
        protected PatternSearchService $patternSearchService,
    ) {
    }

    #[Route('/api/pattern-search', name: 'pattern_search', methods: ['GET'])]
    #[OA\Get(
        path: '/api/pattern-search',
        summary: 'Search for words matching a pattern with unknown letters'
    )]
    #[OA\Parameter(
        name: 'pattern',
        description: 'Search pattern where ? represents a single unknown character (e.g., h?s? matches hose, hash, husk)',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'h?s?')
    )]
    #[OA\Parameter(
        name: 'languageCode',
        description: 'Optional language code to filter results (e.g., en, ka, ru)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'en')
    )]
    #[OA\Parameter(
        name: 'field',
        description: 'Field to search in: word (default) or ipa',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'word', enum: ['word', 'ipa'])
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Maximum number of results to return',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 100, maximum: 1000, minimum: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful pattern search',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'word', type: 'string', example: 'hose'),
                    new OA\Property(property: 'ipa', type: 'string', example: 'hoʊz'),
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
                new OA\Property(property: 'error', type: 'string', example: 'Pattern parameter is required'),
            ],
            type: 'object'
        )
    )]
    public function search(Request $request): JsonResponse
    {
        $pattern = $request->query->get('pattern');
        $languageCode = $request->query->get('languageCode');
        $field = $request->query->get('field', 'word');
        $limit = (int) $request->query->get('limit', 100);

        if (!$pattern) {
            return new JsonResponse(
                ['error' => 'Pattern parameter is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!in_array($field, ['word', 'ipa'])) {
            return new JsonResponse(
                ['error' => 'Field parameter must be either "word" or "ipa"'],
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
            if ('ipa' == $field) {
                $results = $this->patternSearchService->findByIpaPattern($pattern, $languageCode, $limit);
            } else {
                $results = $this->patternSearchService->findByPattern($pattern, $languageCode, $limit);
            }

            return new JsonResponse($results, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred during pattern search: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
