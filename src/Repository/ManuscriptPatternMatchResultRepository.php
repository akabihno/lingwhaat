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

    public function upsert(int $matchId, string $results): void
    {
        $entity = $this->findOneBy(['matchId' => $matchId]);

        if ($entity === null) {
            $entity = (new ManuscriptPatternMatchResultEntity())->setMatchId($matchId);
        }

        $entity->setResults($results)
            ->setTsCreated((new \DateTimeImmutable())->format('Y-m-d H:i:s'));

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}
