<?php

namespace App\Service\Search;

use App\Repository\AfrikaansLanguageRepository;
use App\Repository\AlbanianLanguageRepository;
use App\Repository\ArmenianLanguageRepository;
use App\Repository\CzechLanguageRepository;
use App\Repository\DutchLanguageRepository;
use App\Repository\EnglishLanguageRepository;
use App\Repository\EstonianLanguageRepository;
use App\Repository\FrenchLanguageRepository;
use App\Repository\GeorgianLanguageRepository;
use App\Repository\GermanLanguageRepository;
use App\Repository\GreekLanguageRepository;
use App\Repository\HindiLanguageRepository;
use App\Repository\ItalianLanguageRepository;
use App\Repository\LatinLanguageRepository;
use App\Repository\LatvianLanguageRepository;
use App\Repository\LithuanianLanguageRepository;
use App\Repository\PolishLanguageRepository;
use App\Repository\PortugueseLanguageRepository;
use App\Repository\RomanianLanguageRepository;
use App\Repository\RussianLanguageRepository;
use App\Repository\SerboCroatianLanguageRepository;
use App\Repository\SpanishLanguageRepository;
use App\Repository\SwedishLanguageRepository;
use App\Repository\TagalogLanguageRepository;
use App\Repository\TurkishLanguageRepository;
use App\Repository\UkrainianLanguageRepository;
use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\LanguageDetection\LanguageServices\AfrikaansLanguageService;
use App\Service\LanguageDetection\LanguageServices\AlbanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\ArmenianLanguageService;
use App\Service\LanguageDetection\LanguageServices\CzechLanguageService;
use App\Service\LanguageDetection\LanguageServices\DutchLanguageService;
use App\Service\LanguageDetection\LanguageServices\EnglishLanguageService;
use App\Service\LanguageDetection\LanguageServices\EstonianLanguageService;
use App\Service\LanguageDetection\LanguageServices\FrenchLanguageService;
use App\Service\LanguageDetection\LanguageServices\GeorgianLanguageService;
use App\Service\LanguageDetection\LanguageServices\GermanLanguageService;
use App\Service\LanguageDetection\LanguageServices\GreekLanguageService;
use App\Service\LanguageDetection\LanguageServices\HindiLanguageService;
use App\Service\LanguageDetection\LanguageServices\ItalianLanguageService;
use App\Service\LanguageDetection\LanguageServices\LatinLanguageService;
use App\Service\LanguageDetection\LanguageServices\LatvianLanguageService;
use App\Service\LanguageDetection\LanguageServices\LithuanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\PolishLanguageService;
use App\Service\LanguageDetection\LanguageServices\PortugueseLanguageService;
use App\Service\LanguageDetection\LanguageServices\RomanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\RussianLanguageService;
use App\Service\LanguageDetection\LanguageServices\SerboCroatianLanguageService;
use App\Service\LanguageDetection\LanguageServices\SpanishLanguageService;
use App\Service\LanguageDetection\LanguageServices\SwedishLanguageService;
use App\Service\LanguageDetection\LanguageServices\TagalogLanguageService;
use App\Service\LanguageDetection\LanguageServices\TurkishLanguageService;
use App\Service\LanguageDetection\LanguageServices\UkrainianLanguageService;
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



        foreach (LanguageDetectionService::getLanguageCodes() as $languageCode) {
            switch ($languageCode) {
                case LanguageDetectionService::FRENCH_LANGUAGE_CODE:
                    $service = new FrenchLanguageService(new FrenchLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::GERMAN_LANGUAGE_CODE:
                    $service = new GermanLanguageService(new GermanLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::GREEK_LANGUAGE_CODE:
                    $service = new GreekLanguageService(new GreekLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::ITALIAN_LANGUAGE_CODE:
                    $service = new ItalianLanguageService(new ItalianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::LATVIAN_LANGUAGE_CODE:
                    $service = new LatvianLanguageService(new LatvianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::LITHUANIAN_LANGUAGE_CODE:
                    $service = new LithuanianLanguageService(new LithuanianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::POLISH_LANGUAGE_CODE:
                    $service = new PolishLanguageService(new PolishLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::PORTUGUESE_LANGUAGE_CODE:
                    $service = new PortugueseLanguageService(new PortugueseLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::ROMANIAN_LANGUAGE_CODE:
                    $service = new RomanianLanguageService(new RomanianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::RUSSIAN_LANGUAGE_CODE:
                    $service = new RussianLanguageService(new RussianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::SERBOCROATIAN_LANGUAGE_CODE:
                    $service = new SerboCroatianLanguageService(new SerboCroatianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::TAGALOG_LANGUAGE_CODE:
                    $service = new TagalogLanguageService(new TagalogLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::UKRAINIAN_LANGUAGE_CODE:
                    $service = new UkrainianLanguageService(new UkrainianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::SPANISH_LANGUAGE_CODE:
                    $service = new SpanishLanguageService(new SpanishLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::LATIN_LANGUAGE_CODE:
                    $service = new LatinLanguageService(new LatinLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::SWEDISH_LANGUAGE_CODE:
                    $service = new SwedishLanguageService(new SwedishLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::ESTONIAN_LANGUAGE_CODE:
                    $service = new EstonianLanguageService(new EstonianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::ENGLISH_LANGUAGE_CODE:
                    $service = new EnglishLanguageService(new EnglishLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::DUTCH_LANGUAGE_CODE:
                    $service = new DutchLanguageService(new DutchLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::HINDI_LANGUAGE_CODE:
                    $service = new HindiLanguageService(new HindiLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::GEORGIAN_LANGUAGE_CODE:
                    $service = new GeorgianLanguageService(new GeorgianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::TURKISH_LANGUAGE_CODE:
                    $service = new TurkishLanguageService(new TurkishLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::ALBANIAN_LANGUAGE_CODE:
                    $service = new AlbanianLanguageService(new AlbanianLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::CZECH_LANGUAGE_CODE:
                    $service = new CzechLanguageService(new CzechLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::AFRIKAANS_LANGUAGE_CODE:
                    $service = new AfrikaansLanguageService(new AfrikaansLanguageRepository($this->em));
                    break;
                case LanguageDetectionService::ARMENIAN_LANGUAGE_CODE:
                    $service = new ArmenianLanguageService(new ArmenianLanguageRepository($this->em));
                    break;
            }

            if (isset($service)) {
                $offset = 0;
                $batchSize = self::INDEXING_BATCH_SIZE;

                do {
                    $words = $service->fetchAllEntitiesWithIpa($batchSize, $offset);

                    if (empty($words)) {
                        break;
                    }

                    $docs = [];
                    foreach ($words as $wordEntity) {
                        $docs[] = new Document(null, [
                            'word' => $wordEntity->getName(),
                            'ipa' => $wordEntity->getIpa(),
                            'languageCode' => $languageCode,
                        ]);
                    }

                    //$index->addDocuments($docs, ['refresh' => false]);
                    $this->elasticsearchBulkStreamer->sendBatch($this->indexName, array_map(fn($d) => $d->getData(), $docs));

                    $offset += $batchSize;

                    gc_collect_cycles();
                } while (count($words) === $batchSize);

            }
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