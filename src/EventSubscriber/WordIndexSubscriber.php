<?php

namespace App\EventSubscriber;

use App\Service\Search\WordIndexer;
use App\Service\LanguageDetection\LanguageDetectionService;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Elastica\Document;
use Elastica\Query\Term;

class WordIndexSubscriber
{
    public function __construct(
        private readonly WordIndexer $wordIndexer,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->indexWord($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->indexWord($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->removeWordFromIndex($args);
    }

    private function indexWord(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!method_exists($entity, 'getName') || !method_exists($entity, 'getIpa')) {
            return;
        }

        $languageCode = $this->detectLanguageCodeFromEntity($entity);
        if (!$languageCode) {
            return;
        }

        $index = $this->wordIndexer->getClient()->getIndex($this->wordIndexer->getIndexName());

        $doc = new Document(null, [
            'word' => $entity->getName(),
            'ipa' => $entity->getIpa(),
            'languageCode' => $languageCode,
        ]);

        $index->addDocument($doc);
        $index->refresh();
    }

    private function removeWordFromIndex(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!method_exists($entity, 'getName')) {
            return;
        }

        $index = $this->wordIndexer->getClient()->getIndex($this->wordIndexer->getIndexName());
        $word = $entity->getName();

        $query = new Term(['word' => $word]);
        $index->deleteByQuery($query);
    }

    private function detectLanguageCodeFromEntity(object $entity): ?string
    {
        $class = get_class($entity);
        $map = [
            'AfrikaansLanguageEntity' => LanguageDetectionService::AFRIKAANS_LANGUAGE_CODE,
            'AlbanianLanguageEntity' => LanguageDetectionService::ALBANIAN_LANGUAGE_CODE,
            'ArmenianLanguageEntity' => LanguageDetectionService::ARMENIAN_LANGUAGE_CODE,
            'CzechLanguageEntity' => LanguageDetectionService::CZECH_LANGUAGE_CODE,
            'DutchLanguageEntity' => LanguageDetectionService::DUTCH_LANGUAGE_CODE,
            'EnglishLanguageEntity' => LanguageDetectionService::ENGLISH_LANGUAGE_CODE,
            'EstonianLanguageEntity' => LanguageDetectionService::ESTONIAN_LANGUAGE_CODE,
            'FrenchLanguageEntity' => LanguageDetectionService::FRENCH_LANGUAGE_CODE,
            'GeorgianLanguageEntity' => LanguageDetectionService::GEORGIAN_LANGUAGE_CODE,
            'GermanLanguageEntity' => LanguageDetectionService::GERMAN_LANGUAGE_CODE,
            'GreekLanguageEntity' => LanguageDetectionService::GREEK_LANGUAGE_CODE,
            'HindiLanguageEntity' => LanguageDetectionService::HINDI_LANGUAGE_CODE,
            'ItalianLanguageEntity' => LanguageDetectionService::ITALIAN_LANGUAGE_CODE,
            'LatinLanguageEntity' => LanguageDetectionService::LATIN_LANGUAGE_CODE,
            'LatvianLanguageEntity' => LanguageDetectionService::LATVIAN_LANGUAGE_CODE,
            'LithuanianLanguageEntity' => LanguageDetectionService::LITHUANIAN_LANGUAGE_CODE,
            'PolishLanguageEntity' => LanguageDetectionService::POLISH_LANGUAGE_CODE,
            'PortugueseLanguageEntity' => LanguageDetectionService::PORTUGUESE_LANGUAGE_CODE,
            'RomanianLanguageEntity' => LanguageDetectionService::ROMANIAN_LANGUAGE_CODE,
            'RussianLanguageEntity' => LanguageDetectionService::RUSSIAN_LANGUAGE_CODE,
            'SerboCroatianLanguageEntity' => LanguageDetectionService::SERBOCROATIAN_LANGUAGE_CODE,
            'SpanishLanguageEntity' => LanguageDetectionService::SPANISH_LANGUAGE_CODE,
            'SwedishLanguageEntity' => LanguageDetectionService::SWEDISH_LANGUAGE_CODE,
            'TagalogLanguageEntity' => LanguageDetectionService::TAGALOG_LANGUAGE_CODE,
            'TurkishLanguageEntity' => LanguageDetectionService::TURKISH_LANGUAGE_CODE,
            'UkrainianLanguageEntity' => LanguageDetectionService::UKRAINIAN_LANGUAGE_CODE,
        ];

        foreach ($map as $entityFragment => $code) {
            if (str_contains($class, $entityFragment)) {
                return $code;
            }
        }

        return null;
    }

}