<?php

namespace App\Repository;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WikipediaPatternIndexOffsetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WikipediaPatternIndexOffsetEntity::class);
    }

    public function findByLanguageCode(string $languageCode): ?WikipediaPatternIndexOffsetEntity
    {
        return $this->findOneBy(['languageCode' => $languageCode]);
    }

    /**
     * Return all offset entities keyed by languageCode. Languages with no row yet are absent;
     * callers should treat missing entries as a language that has never been processed.
     *
     * @return array<string, WikipediaPatternIndexOffsetEntity>
     */
    public function findAllKeyedByLanguageCode(): array
    {
        $map = [];
        foreach ($this->findAll() as $entity) {
            $map[$entity->getLanguageCode()] = $entity;
        }
        return $map;
    }

    /**
     * Stamp last_run_at = now for an existing row without touching the ORM identity map.
     * Safe to call before indexBatchByLanguageCode (which calls em->clear()), since a DQL
     * bulk UPDATE goes directly to the database and is unaffected by subsequent clears.
     * Has no effect for languages that do not yet have an offset row.
     */
    public function touchLastRunAt(string $languageCode): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.lastRunAt', ':now')
            ->where('o.languageCode = :languageCode')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->execute();
    }

    public function save(WikipediaPatternIndexOffsetEntity $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}
