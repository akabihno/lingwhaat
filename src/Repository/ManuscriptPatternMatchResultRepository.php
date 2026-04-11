<?php

namespace App\Repository;

use App\Entity\ManuscriptPatternMatchResultEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ManuscriptPatternMatchResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManuscriptPatternMatchResultEntity::class);
    }

    public function upsert(int $matchId, int $sourceId, string $results): void
    {
        $entity = $this->findOneBy(['matchId' => $matchId]);

        if ($entity === null) {
            $entity = (new ManuscriptPatternMatchResultEntity())
                ->setMatchId($matchId)
                ->setSourceId($sourceId);
        }

        $entity->setResults($results)
            ->setTsCreated((new \DateTimeImmutable())->format('Y-m-d H:i:s'));

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * @return ManuscriptPatternMatchResultEntity[]
     */
    public function findUnscored(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.languageScore IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function updateScore(int $id, ?string $languageCode, ?float $languageScore): void
    {
        $entity = $this->find($id);
        if ($entity === null) {
            return;
        }

        $entity->setLanguageCode($languageCode)->setLanguageScore($languageScore);
        $this->getEntityManager()->flush();
    }
}
