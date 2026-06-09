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
     * Return a map of languageCode => lastArticleId for every offset row in the table.
     * Used by the dispatcher to prioritise least-processed languages first (a lower last-processed
     * id means less of the corpus consumed); languages that have no row yet (never indexed) are not
     * in this map and should be treated as cursor 0 by the caller.
     *
     * @return array<string, int>
     */
    public function getOffsetsByLanguageCode(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.languageCode AS languageCode, o.lastArticleId AS lastArticleId')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['languageCode']] = (int) $row['lastArticleId'];
        }
        return $map;
    }

    public function save(WikipediaPatternIndexOffsetEntity $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}
