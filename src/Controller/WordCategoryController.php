<?php

namespace App\Controller;

use App\DTO\WordCategoryData;
use App\Entity\WordCategoryEntity;
use App\Repository\WordCategoryRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Word Categories')]
#[Route('/api/word-category')]
class WordCategoryController extends AbstractController
{
    public function __construct(
        private readonly WordCategoryRepository $repository
    ) {
    }

    #[Route('', name: 'word_category_upsert', methods: ['POST'])]
    #[OA\Post(
        path: '/api/word-category',
        description: 'Upserts semantic category data for a (languageCode, word) pair. Requires X-Api-Key header.',
        summary: 'Create or update word category data',
        security: [['ApiKey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['languageCode', 'word', 'categories'],
                properties: [
                    new OA\Property(property: 'languageCode', type: 'string', example: 'en'),
                    new OA\Property(property: 'word', type: 'string', example: 'horse'),
                    new OA\Property(
                        property: 'categories',
                        description: 'Map of category name to float value [0.0–1.0]. Omit unknown dimensions.',
                        type: 'object',
                        example: ['livingBeing' => 1.0, 'animate' => 1.0, 'animal' => 1.0, 'size' => 0.6]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Record created or updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'languageCode', type: 'string'),
                        new OA\Property(property: 'word', type: 'string'),
                        new OA\Property(property: 'categories', type: 'object'),
                        new OA\Property(property: 'tsCreated', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'tsUpdated', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized — missing or invalid X-Api-Key'),
        ]
    )]
    public function upsert(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        if (!is_array($body)) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $languageCode = $body['languageCode'] ?? null;
        $word = $body['word'] ?? null;
        $categoriesRaw = $body['categories'] ?? null;

        if (empty($languageCode) || empty($word)) {
            return new JsonResponse(
                ['error' => 'Missing required fields: languageCode, word'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_array($categoriesRaw)) {
            return new JsonResponse(
                ['error' => 'Field "categories" must be a JSON object'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $validFields = array_flip(WordCategoryData::getFieldNames());
        $validatedCategories = [];

        foreach ($categoriesRaw as $key => $value) {
            if (!isset($validFields[$key])) {
                return new JsonResponse(
                    ['error' => "Unknown category field: \"$key\""],
                    Response::HTTP_BAD_REQUEST
                );
            }
            if ($value !== null && (!is_numeric($value) || $value < 0.0 || $value > 1.0)) {
                return new JsonResponse(
                    ['error' => "Category \"$key\" must be a float between 0.0 and 1.0, or null"],
                    Response::HTTP_BAD_REQUEST
                );
            }
            if ($value !== null) {
                $validatedCategories[$key] = (float) $value;
            }
        }

        $entity = new WordCategoryEntity();
        $entity->setLanguageCode($languageCode);
        $entity->setWord($word);
        $entity->setCategories($validatedCategories);

        $saved = $this->repository->upsert($entity);

        return $this->json($this->serialize($saved), Response::HTTP_OK);
    }

    #[Route('/{languageCode}/{word}', name: 'word_category_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/word-category/{languageCode}/{word}',
        summary: 'Get category data for a specific word',
        parameters: [
            new OA\Parameter(name: 'languageCode', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'en')),
            new OA\Parameter(name: 'word', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'horse')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category data found'),
            new OA\Response(response: 404, description: 'No category data found for this word'),
        ]
    )]
    public function get(string $languageCode, string $word): JsonResponse
    {
        $entity = $this->repository->findByLanguageCodeAndWord($languageCode, $word);

        if ($entity === null) {
            return new JsonResponse(
                ['error' => "No category data found for word \"$word\" in language \"$languageCode\""],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json($this->serialize($entity));
    }

    private function serialize(WordCategoryEntity $entity): array
    {
        return [
            'id' => $entity->getId(),
            'languageCode' => $entity->getLanguageCode(),
            'word' => $entity->getWord(),
            'categories' => $entity->getCategories(),
            'tsCreated' => $entity->getTsCreated()->format(\DateTimeInterface::ATOM),
            'tsUpdated' => $entity->getTsUpdated()->format(\DateTimeInterface::ATOM),
        ];
    }
}
