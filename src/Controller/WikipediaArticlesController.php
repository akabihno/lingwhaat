<?php

namespace App\Controller;

use App\Constant\LanguageMappings;
use App\Repository\WikipediaArticleRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Wikipedia Articles')]
class WikipediaArticlesController extends AbstractController
{
    private const int DEFAULT_LIMIT = 20;
    private const int MAX_LIMIT = 100;

    public function __construct(
        private readonly WikipediaArticleRepository $wikipediaArticleRepository
    ) {
    }

    #[Route('/api/wikipedia-articles', name: 'wikipedia_articles', methods: ['GET'])]
    #[OA\Get(
        path: '/api/wikipedia-articles',
        description: 'Returns stored Wikipedia articles (id, link, text, creation timestamp) for a language, paginated.',
        summary: 'Get Wikipedia articles for a language from the database',
        parameters: [
            new OA\Parameter(
                name: 'languageCode',
                description: 'Language code (e.g. en, de, nl)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'en')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Max number of articles to return (max 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: self::DEFAULT_LIMIT, example: 20)
            ),
            new OA\Parameter(
                name: 'offset',
                description: 'Pagination offset',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0, example: 0)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of articles',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'languageCode', type: 'string', example: 'en'),
                        new OA\Property(property: 'total', description: 'Total articles stored for this language', type: 'integer', example: 12345),
                        new OA\Property(property: 'count', description: 'Articles in this page', type: 'integer', example: 20),
                        new OA\Property(property: 'limit', type: 'integer', example: 20),
                        new OA\Property(property: 'offset', type: 'integer', example: 0),
                        new OA\Property(
                            property: 'articles',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 885859),
                                    new OA\Property(property: 'wikipediaLink', type: 'string', example: 'https://en.wikipedia.org/wiki/Horse'),
                                    new OA\Property(property: 'text', type: 'string', example: 'The horse is a domesticated, one-toed, hoofed mammal...'),
                                    new OA\Property(property: 'tsCreated', type: 'string', example: '2025-01-14 10:22:03'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Unknown or missing languageCode'),
            new OA\Response(response: 500, description: 'Internal server error'),
        ]
    )]
    public function getArticles(Request $request): JsonResponse
    {
        $languageCode = (string) $request->query->get('languageCode', '');
        $limit = min(max($request->query->getInt('limit', self::DEFAULT_LIMIT), 1), self::MAX_LIMIT);
        $offset = max($request->query->getInt('offset', 0), 0);

        if (!in_array($languageCode, LanguageMappings::getLanguageCodes(), true)) {
            return new JsonResponse(
                ['error' => "Unknown language code: \"$languageCode\""],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $articles = $this->wikipediaArticleRepository->findByLanguageCodePaginatedOrdered($languageCode, $limit, $offset);
            $total = $this->wikipediaArticleRepository->countByLanguageCode($languageCode);
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Article retrieval failed', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json([
            'languageCode' => $languageCode,
            'total' => $total,
            'count' => count($articles),
            'limit' => $limit,
            'offset' => $offset,
            'articles' => $articles,
        ]);
    }
}
