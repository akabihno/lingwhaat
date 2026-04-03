<?php

namespace App\Controller;

use App\Constant\LanguageMappings;
use App\Repository\AbstractLanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Language Words')]
class LanguageWordsController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    #[Route('/api/language-words', name: 'language_words', methods: ['GET'])]
    #[OA\Get(
        path: '/api/language-words',
        description: 'Returns words (name, ipa, score) from the language-specific MySQL table, paginated.',
        summary: 'Get words for a language from the database',
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
                description: 'Max number of words to return (max 50000)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1000, example: 1000)
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
                description: 'List of words',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'languageCode', type: 'string', example: 'en'),
                        new OA\Property(property: 'total', type: 'integer', example: 1000),
                        new OA\Property(property: 'offset', type: 'integer', example: 0),
                        new OA\Property(
                            property: 'words',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'name', type: 'string', example: 'horse'),
                                    new OA\Property(property: 'ipa', type: 'string', example: 'hɔːs'),
                                    new OA\Property(property: 'score', type: 'integer', example: 42),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Unknown or missing languageCode'),
        ]
    )]
    public function getWords(Request $request): JsonResponse
    {
        $languageCode = $request->query->get('languageCode', '');
        $limit = min((int) $request->query->get('limit', 1000), AbstractLanguageRepository::PRONUNCIATION_MAX_RESULTS);
        $offset = max((int) $request->query->get('offset', 0), 0);

        $entityClass = LanguageMappings::getEntityClassByLanguageCode($languageCode);

        if ($entityClass === null) {
            return new JsonResponse(
                ['error' => "Unknown language code: \"$languageCode\""],
                Response::HTTP_BAD_REQUEST
            );
        }

        /** @var AbstractLanguageRepository $repository */
        $repository = $this->registry->getRepository($entityClass);
        $words = $repository->findAllNamesIpaAndScore($limit, $offset);

        return $this->json([
            'languageCode' => $languageCode,
            'total' => count($words),
            'offset' => $offset,
            'words' => $words,
        ]);
    }
}
