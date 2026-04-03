<?php

namespace App\Service\Search;

use App\DTO\WordCategoryData;
use App\Entity\WordCategoryEntity;
use App\Repository\WordCategoryRepository;
use Elastica\Client;
use Elastica\Document;

class WordCategoryIndexer
{
    public const INDEX_NAME = 'word_categories_index';
    private const BATCH_SIZE = 500;
    private const VECTOR_DIMS = 1000;

    private Client $esClient;
    /** @var string[] */
    private static array $fieldNames = [];

    public function __construct(
        private readonly WordCategoryRepository $repository,
    ) {
        $this->esClient = ElasticsearchClientFactory::create();

        if (empty(self::$fieldNames)) {
            self::$fieldNames = WordCategoryData::getFieldNames();
        }
    }

    public function createIndex(bool $dropExisting = true): void
    {
        $index = $this->esClient->getIndex(self::INDEX_NAME);

        if ($dropExisting && $index->exists()) {
            $index->delete();
        }

        if (!$index->exists()) {
            $index->create([
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'word' => ['type' => 'keyword'],
                        'languageCode' => ['type' => 'keyword'],
                        'categoryVector' => [
                            'type' => 'dense_vector',
                            'dims' => self::VECTOR_DIMS,
                            'index' => true,
                            'similarity' => 'cosine',
                        ],
                        'categoriesCount' => ['type' => 'integer'],
                    ],
                ],
            ]);
        }
    }

    /**
     * Indexes all word_category rows for a given language from the DB into Elasticsearch.
     *
     * @return int Number of documents indexed
     */
    public function reindexByLanguage(string $languageCode): int
    {
        $index = $this->esClient->getIndex(self::INDEX_NAME);
        $offset = 0;
        $total = 0;

        do {
            $entities = $this->repository->findByLanguageCode($languageCode, self::BATCH_SIZE, $offset);

            if (empty($entities)) {
                break;
            }

            $docs = array_map(
                fn(WordCategoryEntity $e) => new Document(
                    self::buildDocId($e->getLanguageCode(), $e->getWord()),
                    $this->buildDocData($e)
                ),
                $entities
            );

            $index->addDocuments($docs);

            $total += count($entities);
            $offset += self::BATCH_SIZE;

            unset($docs, $entities);
            gc_collect_cycles();
        } while (true);

        $index->refresh();

        return $total;
    }

    /**
     * Indexes or updates a single entity in Elasticsearch.
     */
    public function indexEntity(WordCategoryEntity $entity): void
    {
        $index = $this->esClient->getIndex(self::INDEX_NAME);
        $doc = new Document(
            self::buildDocId($entity->getLanguageCode(), $entity->getWord()),
            $this->buildDocData($entity)
        );
        $index->addDocument($doc);
        $index->refresh();
    }

    /**
     * Removes a single document from the index.
     */
    public function deleteDocument(string $languageCode, string $word): void
    {
        $index = $this->esClient->getIndex(self::INDEX_NAME);
        $index->deleteById(self::buildDocId($languageCode, $word));
        $index->refresh();
    }

    /**
     * Converts a sparse categories map into a 1000-dim dense float vector.
     * Unknown dimensions default to 0.0.
     *
     * @param array<string, float> $categories
     * @return float[]
     */
    public function buildVector(array $categories): array
    {
        $vector = [];
        foreach (self::$fieldNames as $field) {
            $vector[] = (float) ($categories[$field] ?? 0.0);
        }
        return $vector;
    }

    public function getClient(): Client
    {
        return $this->esClient;
    }

    private function buildDocData(WordCategoryEntity $entity): array
    {
        return [
            'word' => $entity->getWord(),
            'languageCode' => $entity->getLanguageCode(),
            'categoryVector' => $this->buildVector($entity->getCategories()),
            'categoriesCount' => count($entity->getCategories()),
        ];
    }

    private static function buildDocId(string $languageCode, string $word): string
    {
        return $languageCode . '_' . $word;
    }
}
