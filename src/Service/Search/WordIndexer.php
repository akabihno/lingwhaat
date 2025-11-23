<?php

namespace App\Service\Search;

use App\Constant\LanguageServicesAndCodes;
use App\Repository\AfarLanguageRepository;
use App\Repository\AfrikaansLanguageRepository;
use App\Repository\AlbanianLanguageRepository;
use App\Repository\ArabicLanguageRepository;
use App\Repository\ArmenianLanguageRepository;
use App\Repository\BengaliLanguageRepository;
use App\Repository\BretonLanguageRepository;
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
use App\Repository\KazakhLanguageRepository;
use App\Repository\LatinLanguageRepository;
use App\Repository\LatvianLanguageRepository;
use App\Repository\LithuanianLanguageRepository;
use App\Repository\OldDutchLanguageRepository;
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
use App\Repository\UzbekLanguageRepository;
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



        foreach (LanguageServicesAndCodes::getLanguageCodes() as $languageCode) {
            switch ($languageCode) {
                case LanguageServicesAndCodes::FRENCH_LANGUAGE_CODE:
                    $repository = new FrenchLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::GERMAN_LANGUAGE_CODE:
                    $repository = new GermanLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::GREEK_LANGUAGE_CODE:
                    $repository = new GreekLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::ITALIAN_LANGUAGE_CODE:
                    $repository = new ItalianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::LATVIAN_LANGUAGE_CODE:
                    $repository = new LatvianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::LITHUANIAN_LANGUAGE_CODE:
                    $repository = new LithuanianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::POLISH_LANGUAGE_CODE:
                    $repository = new PolishLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::PORTUGUESE_LANGUAGE_CODE:
                    $repository = new PortugueseLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::ROMANIAN_LANGUAGE_CODE:
                    $repository = new RomanianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::RUSSIAN_LANGUAGE_CODE:
                    $repository = new RussianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::SERBOCROATIAN_LANGUAGE_CODE:
                    $repository = new SerboCroatianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::TAGALOG_LANGUAGE_CODE:
                    $repository = new TagalogLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::UKRAINIAN_LANGUAGE_CODE:
                    $repository = new UkrainianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::SPANISH_LANGUAGE_CODE:
                    $repository = new SpanishLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::LATIN_LANGUAGE_CODE:
                    $repository = new LatinLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::SWEDISH_LANGUAGE_CODE:
                    $repository = new SwedishLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::ESTONIAN_LANGUAGE_CODE:
                    $repository = new EstonianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::ENGLISH_LANGUAGE_CODE:
                    $repository = new EnglishLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::DUTCH_LANGUAGE_CODE:
                    $repository = new DutchLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::HINDI_LANGUAGE_CODE:
                    $repository = new HindiLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::GEORGIAN_LANGUAGE_CODE:
                    $repository = new GeorgianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::TURKISH_LANGUAGE_CODE:
                    $repository = new TurkishLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::ALBANIAN_LANGUAGE_CODE:
                    $repository = new AlbanianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::CZECH_LANGUAGE_CODE:
                    $repository = new CzechLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::AFRIKAANS_LANGUAGE_CODE:
                    $repository = new AfrikaansLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::ARMENIAN_LANGUAGE_CODE:
                    $repository = new ArmenianLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::AFAR_LANGUAGE_CODE:
                    $repository = new AfarLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::BENGALI_LANGUAGE_CODE:
                    $repository = new BengaliLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::UZBEK_LANGUAGE_CODE:
                    $repository = new UzbekLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::BRETON_LANGUAGE_CODE:
                    $repository = new BretonLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::KAZAKH_LANGUAGE_CODE:
                    $repository = new KazakhLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::ARABIC_LANGUAGE_CODE:
                    $repository = new ArabicLanguageRepository($this->em);
                    break;
                case LanguageServicesAndCodes::OLD_DUTCH_LANGUAGE_CODE:
                    $repository = new OldDutchLanguageRepository($this->em);
                    break;

            }

            if (isset($repository)) {
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
                            'word' =>  $row['name'],
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