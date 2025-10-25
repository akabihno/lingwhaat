<?php

namespace App\EventSubscriber;

use App\Constant\LanguageServicesAndCodes;
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

        $languageCode = LanguageServicesAndCodes::detectLanguageCodeFromEntity($entity);
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

}