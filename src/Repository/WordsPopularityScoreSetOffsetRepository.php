<?php

namespace App\Repository;

use App\Entity\WordsPopularityScoreSetOffsetEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WordsPopularityScoreSetOffsetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordsPopularityScoreSetOffsetEntity::class);
    }

    public function findByLanguageCode(string $languageCode): ?WordsPopularityScoreSetOffsetEntity
    {
        return $this->findOneBy(['languageCode' => $languageCode]);
    }

    /**
     * Return the offset row for a language, creating one at offset 0 if it does not exist yet.
     * Guarantees incrementOffsetByLanguageCode (a bulk UPDATE) has a row to advance.
     */
    public function getOrCreate(string $languageCode): WordsPopularityScoreSetOffsetEntity
    {
        $entity = $this->findByLanguageCode($languageCode);

        if ($entity === null) {
            $entity = (new WordsPopularityScoreSetOffsetEntity())
                ->setLanguageCode($languageCode)
                ->setOffset(0);
            $this->save($entity);
        }

        return $entity;
    }

    /**
     * Advance the cursor by $increment for an existing row using a DQL bulk UPDATE, so it goes
     * straight to the database and is unaffected by a preceding EntityManager::clear().
     */
    public function incrementOffsetByLanguageCode(string $languageCode, int $increment): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.offset', 'o.offset + :increment')
            ->where('o.languageCode = :languageCode')
            ->setParameter('increment', $increment)
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->execute();
    }

    public function save(WordsPopularityScoreSetOffsetEntity $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}
