<?php

namespace App\Controller;

use App\Service\Search\WikipediaArticleSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WikipediaArticleSearchController extends AbstractController
{
    public function __construct(
        protected WikipediaArticleSearchService $wikipediaArticleSearchService,
    ) {
    }

    #[Route('/api/wikipedia-article-search', name: 'search_wiki_article', methods: ['GET'])]
    #[OA\Get(
        path: '/api/wikipedia-article-search',
        description: 'Returns text of Wikipedia article from the DB',
        summary: 'Find Wikipedia article by id',
        tags: ['Pattern Search'],
        parameters: [
            new OA\Parameter(
                name: 'articleId',
                description: 'Id of the Wikipedia article to search for',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 885859)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Text of matched Wikipedia article',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'text', description: 'Article text', type: 'string', example: 'Czerwone i białe – polski film obyczajowy z 1975 roku w reżyserii Pawła Komorowskiego. Adaptacja powieści Jana Pierzchały pt. Krzak gorejący. Roboczy tytuł filmu, pod którym był anonsowany w prasie branżowej, nosił tytuł pierwowzoru literackiego. Obraz nie spotkał się z uznaniem krytyki. Fabuła[edytuj | edytuj kod]
Skomplikowane i często tragiczne losy Polaków podczas obydwu wojen światowych oraz okresu międzywojennego ukazane na przykładzie Rafała Naziemca, jego bliskich i znajomych. Obsada[edytuj | edytuj kod]
Czesław Jaroszyński – Rafał Naziemiec
Henryk Bista – Karol Krauze, przyrodni brat Rafała
Elżbieta Jodłowska – Jadwiga, siostra Rafała
Anna Milewska – Nascia Radwanówna
Jan Englert – Edward Ocieski
Ludwik Benoit – przewoźnik
Jadwiga Chojnacka – Elżbieta Taniejewa
Bogusław Sochnacki – podoficer rosyjski
Lech Grzmociński – „Czarniawy”
Jerzy Kamas – „Bystry”
Jerzy Nowak – Żyd
Wirgiliusz Gryń – spadochroniarz radziecki
Hanna Bedryńska – ciotka
Irena Burawska – matka
Henryk Dłużyński – prokurator
Marek Dobrowolski – mąż Ireny
Ewa Maria Hesse – Irena
Celina Klimczakówna – wdowa
Tadeusz Madeja – Niżko
Zygmunt Malawski – sędzia
Bohdan Mikuć – komunista
Walentyna Mołdawanowa – Rosjanka
Wojciech Pilarski – adwokat
Jadwiga Siennicka – gospodyni
Józef Zbiróg – śledczy
Jan Zdrojewski – carski oficer
Przypisy[edytuj | edytuj kod] ↑ Elżbieta Dolińska. Życiorys. „Film”. Nr 7(107), s. 14–15, 1975-02-16. Warszawa: RWS -„Prasa-Książka-Ruch”.  ↑ Czesław Dondziłło. Forma jest treścią. „Film”. Nr 39(139), s. 06, 1975-09-28. Warszawa: RWS -„Prasa-Książka-Ruch”.  Bibliografia[edytuj | edytuj kod]
Jan Słodowski: Leksykon polskich filmów fabularnych. Warszawa: Wiedza i Życie, 1997, s. 583–584. ISBN 83-7184-928-1.
Linki zewnętrzne[edytuj | edytuj kod]
Czerwone i białe w bazie IMDb (ang.)
Czerwone i białe w bazie Filmweb
Czerwone i białe w bazie filmpolski.pl
Zdjęcia z filmu Czerwone i białe w bazie Filmoteki Narodowej „Fototeka”
pdeFilmy i seriale w reżyserii Pawła Komorowskiego')
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
        $articleId = $request->query->get('articleId');

        if (empty($articleId)) {
            return new JsonResponse(
                ['error' => 'Missing required parameter: articleId'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $match = $this->wikipediaArticleSearchService->get($articleId);
            return $this->json($match, Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Search failed', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }

}