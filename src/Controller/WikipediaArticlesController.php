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
                description: 'Pagination offset. Ignored when afterId is given. OFFSET-based paging costs grow with depth (MySQL must skip that many rows), so prefer afterId for deep/iterative sweeps.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0, example: 0)
            ),
            new OA\Parameter(
                name: 'afterId',
                description: 'Keyset cursor: return articles with id > afterId, ascending. Takes precedence over offset when present. Cost stays flat regardless of how deep into the corpus afterId is (a single index range seek), unlike offset. Feed back the response\'s nextAfterId to page forward.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 90000)
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
                        new OA\Property(property: 'afterId', description: 'The afterId cursor used for this request, if any', type: 'integer', example: 90000, nullable: true),
                        new OA\Property(property: 'nextAfterId', description: 'id of the last returned article; pass as afterId to fetch the next page. Null when no articles were returned', type: 'integer', example: 90142, nullable: true),
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
        $usingCursor = $request->query->has('afterId');
        $afterId = max($request->query->getInt('afterId', 0), 0);

        if (!in_array($languageCode, LanguageMappings::getLanguageCodes(), true)) {
            return new JsonResponse(
                ['error' => "Unknown language code: \"$languageCode\""],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $articles = $usingCursor
                ? $this->wikipediaArticleRepository->findByLanguageCodeAfterIdOrdered($languageCode, $limit, $afterId)
                : $this->wikipediaArticleRepository->findByLanguageCodePaginatedOrdered($languageCode, $limit, $offset);
            $total = $this->wikipediaArticleRepository->countByLanguageCode($languageCode);
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Article retrieval failed', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $lastArticle = $articles === [] ? null : $articles[count($articles) - 1];

        return $this->json([
            'languageCode' => $languageCode,
            'total' => $total,
            'count' => count($articles),
            'limit' => $limit,
            'offset' => $offset,
            'afterId' => $usingCursor ? $afterId : null,
            'nextAfterId' => $lastArticle['id'] ?? null,
            'articles' => $articles,
        ]);
    }
}
