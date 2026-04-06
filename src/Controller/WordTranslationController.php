<?php

namespace App\Controller;

use App\Repository\WordCategoryRepository;
use App\Service\Search\WordCategoryIndexer;
use App\Service\Search\WordCategorySearchService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Translation')]
#[Route('/api/translation')]
class WordTranslationController extends AbstractController
{
    private const MAX_WORDS_PER_REQUEST = 50;
    private const MAX_RESULTS_PER_WORD = 20;

    public function __construct(
        private readonly WordCategoryRepository $categoryRepository,
        private readonly WordCategoryIndexer $indexer,
        private readonly WordCategorySearchService $searchService,
    ) {
    }

    #[Route('', name: 'word_translation', methods: ['POST'])]
    #[OA\Post(
        path: '/api/translation',
        description: <<<'DESC'
Translates words from a source language to a target language using semantic category similarity.

For each source word, the endpoint fetches its 1000-dimensional category vector from the database,
then runs a kNN cosine-similarity search in Elasticsearch against all indexed words in the target
language. The closest matches are returned ordered by semantic similarity score.

Words that have no category data in the database are returned in the `notFound` array.
DESC,
        summary: 'Semantically translate words between languages via category vectors',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sourceLanguageCode', 'targetLanguageCode', 'words'],
                properties: [
                    new OA\Property(property: 'sourceLanguageCode', type: 'string', example: 'en',
                        description: 'BCP-47 language code of the source words'),
                    new OA\Property(property: 'targetLanguageCode', type: 'string', example: 'de',
                        description: 'BCP-47 language code to translate into'),
                    new OA\Property(
                        property: 'words',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['dog', 'ocean', 'freedom'],
                        description: 'Source words to translate (max 50 per request)'
                    ),
                    new OA\Property(property: 'limit', type: 'integer', example: 5,
                        description: 'Max translation candidates per word (1–20, default 5)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translation results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'sourceLanguageCode', type: 'string', example: 'en'),
                        new OA\Property(property: 'targetLanguageCode', type: 'string', example: 'de'),
                        new OA\Property(
                            property: 'translations',
                            type: 'object',
                            description: 'Map of source word → ranked list of target-language candidates',
                            example: [
                                'dog' => [
                                    ['word' => 'Hund', 'score' => 0.9821],
                                    ['word' => 'Köter', 'score' => 0.8744],
                                ],
                            ]
                        ),
                        new OA\Property(
                            property: 'notFound',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            description: 'Words with no category data in the database'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 503, description: 'Elasticsearch unavailable'),
        ]
    )]
    public function translate(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        if (!is_array($body)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $sourceLanguageCode = trim((string) ($body['sourceLanguageCode'] ?? ''));
        $targetLanguageCode = trim((string) ($body['targetLanguageCode'] ?? ''));
        $words = $body['words'] ?? [];
        $limit = min(max(1, (int) ($body['limit'] ?? 5)), self::MAX_RESULTS_PER_WORD);

        if ($sourceLanguageCode === '') {
            return new JsonResponse(['error' => 'Missing required field: sourceLanguageCode'], Response::HTTP_BAD_REQUEST);
        }

        if ($targetLanguageCode === '') {
            return new JsonResponse(['error' => 'Missing required field: targetLanguageCode'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($words) || empty($words)) {
            return new JsonResponse(['error' => 'Field "words" must be a non-empty array'], Response::HTTP_BAD_REQUEST);
        }

        if (count($words) > self::MAX_WORDS_PER_REQUEST) {
            return new JsonResponse(
                ['error' => sprintf('Too many words. Maximum %d per request.', self::MAX_WORDS_PER_REQUEST)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $translations = [];
        $notFound = [];

        foreach ($words as $word) {
            $word = (string) $word;

            $entity = $this->categoryRepository->findByLanguageCodeAndWord($sourceLanguageCode, $word);

            if ($entity === null) {
                $notFound[] = $word;
                continue;
            }

            try {
                $vector = $this->indexer->buildVector($entity->getCategories());
                $results = $this->searchService->findSimilarByVector($vector, $targetLanguageCode, $limit);
                $translations[$word] = $results;
            } catch (\Throwable $e) {
                return new JsonResponse(
                    ['error' => 'Search service unavailable: ' . $e->getMessage()],
                    Response::HTTP_SERVICE_UNAVAILABLE
                );
            }
        }

        return $this->json([
            'sourceLanguageCode' => $sourceLanguageCode,
            'targetLanguageCode' => $targetLanguageCode,
            'translations' => $translations,
            'notFound' => $notFound,
        ]);
    }

    #[Route('/multi', name: 'word_translation_multi', methods: ['POST'])]
    #[OA\Post(
        path: '/api/translation/multi',
        description: <<<'DESC'
Translates words from a source language into multiple target languages simultaneously.

For each source word the endpoint builds its category vector, then runs a single kNN search
filtered to the union of all target languages. Results are grouped by language code.
DESC,
        summary: 'Translate words into multiple target languages at once',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sourceLanguageCode', 'targetLanguageCodes', 'words'],
                properties: [
                    new OA\Property(property: 'sourceLanguageCode', type: 'string', example: 'en'),
                    new OA\Property(
                        property: 'targetLanguageCodes',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['de', 'fr', 'es']
                    ),
                    new OA\Property(
                        property: 'words',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['dog', 'ocean']
                    ),
                    new OA\Property(property: 'limitPerLanguage', type: 'integer', example: 3,
                        description: 'Max candidates per target language (1–10, default 3)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Multi-language translation results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'sourceLanguageCode', type: 'string'),
                        new OA\Property(
                            property: 'translations',
                            type: 'object',
                            description: 'Map of source word → map of target language → candidates',
                            example: [
                                'dog' => [
                                    'de' => [['word' => 'Hund', 'score' => 0.98]],
                                    'fr' => [['word' => 'chien', 'score' => 0.97]],
                                ],
                            ]
                        ),
                        new OA\Property(property: 'notFound', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 503, description: 'Elasticsearch unavailable'),
        ]
    )]
    public function translateMulti(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        if (!is_array($body)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $sourceLanguageCode = trim((string) ($body['sourceLanguageCode'] ?? ''));
        $targetLanguageCodes = $body['targetLanguageCodes'] ?? [];
        $words = $body['words'] ?? [];
        $limitPerLanguage = min(max(1, (int) ($body['limitPerLanguage'] ?? 3)), 10);

        if ($sourceLanguageCode === '') {
            return new JsonResponse(['error' => 'Missing required field: sourceLanguageCode'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($targetLanguageCodes) || empty($targetLanguageCodes)) {
            return new JsonResponse(['error' => 'Field "targetLanguageCodes" must be a non-empty array'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($words) || empty($words)) {
            return new JsonResponse(['error' => 'Field "words" must be a non-empty array'], Response::HTTP_BAD_REQUEST);
        }

        if (count($words) > self::MAX_WORDS_PER_REQUEST) {
            return new JsonResponse(
                ['error' => sprintf('Too many words. Maximum %d per request.', self::MAX_WORDS_PER_REQUEST)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $translations = [];
        $notFound = [];

        foreach ($words as $word) {
            $word = (string) $word;

            $entity = $this->categoryRepository->findByLanguageCodeAndWord($sourceLanguageCode, $word);

            if ($entity === null) {
                $notFound[] = $word;
                continue;
            }

            try {
                $vector = $this->indexer->buildVector($entity->getCategories());
                $results = $this->searchService->findSimilarAcrossLanguages($vector, $targetLanguageCodes, $limitPerLanguage);
                $translations[$word] = $results;
            } catch (\Throwable $e) {
                return new JsonResponse(
                    ['error' => 'Search service unavailable: ' . $e->getMessage()],
                    Response::HTTP_SERVICE_UNAVAILABLE
                );
            }
        }

        return $this->json([
            'sourceLanguageCode' => $sourceLanguageCode,
            'translations' => $translations,
            'notFound' => $notFound,
        ]);
    }
}
