<?php

namespace App\Service\Search;

use App\Constant\LanguageMappings;
use App\Repository\AbstractLanguageRepository;
use Doctrine\Persistence\ManagerRegistry;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastica\Client;
use Elastica\Document;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WordIndexer
{
    const int INDEXING_BATCH_SIZE = 1000;
    private Client $esClient;
    private string $indexName = 'words_index';

    public function __construct(
        private ManagerRegistry $em,
        private ElasticsearchBulkStreamer $elasticsearchBulkStreamer
    )
    {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    private function getAllLanguageEntities(): array
    {
        $entityManager = $this->em->getManager();
        $metadataFactory = $entityManager->getMetadataFactory();
        $allMetadata = $metadataFactory->getAllMetadata();

        $languageEntities = [];

        foreach ($allMetadata as $metadata) {
            $entityClass = $metadata->getName();

            if (str_ends_with($entityClass, LanguageMappings::LANGUAGE_ENTITY)) {
                $languageEntities[] = $entityClass;
            }
        }

        return $languageEntities;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws ClientResponseException
     * @throws ServerExceptionInterface
     * @throws MissingParameterException
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerResponseException
     */
    public function reindexAll(): void
    {
        $index = $this->esClient->getIndex($this->indexName);

        if ($index->exists()) {
            $index->delete();
        }

        $index->create([
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'default' => [
                            'type' => 'standard',
                            'stopwords' => '_none_'
                        ]
                    ]
                ]
            ],
            'mappings' => [
                'properties' => [
                    'word' => ['type' => 'text'],
                    'ipa' => ['type' => 'text'],
                    'languageCode' => ['type' => 'keyword']
                ]
            ]
        ]);

        $languageEntities = $this->getAllLanguageEntities();

        foreach ($languageEntities as $entityClass) {
            /** @var AbstractLanguageRepository $repository */
            $repository = $this->em->getRepository($entityClass);

            $languageCode = LanguageMappings::detectLanguageCodeFromEntity(new $entityClass());

            if (!$languageCode) {
                continue;
            }

            $offset = 0;
            $batchSize = self::INDEXING_BATCH_SIZE;

            do {
                $rows = $repository->findAllNamesAndIpa($batchSize, $offset);

                if (empty($rows)) {
                    break;
                }

                $docs = [];
                foreach ($rows as $row) {
                    $docs[] = new Document(null, [
                        'word' => $row['name'],
                        'ipa' => $row['ipa'],
                        'languageCode' => $languageCode,
                    ]);
                }

                $this->elasticsearchBulkStreamer->sendBatch($this->indexName, array_map(fn($d) => $d->getData(), $docs));

                unset($docs);
                gc_collect_cycles();

                $offset += $batchSize;
            } while (count($rows) === $batchSize);
        }

        $index->refresh();
    }

    public function getClient(): Client
    {
        return $this->esClient;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

}