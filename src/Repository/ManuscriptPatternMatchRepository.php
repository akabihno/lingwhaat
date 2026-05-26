<?php

namespace App\Repository;

use App\Entity\ManuscriptPatternMatchEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ManuscriptPatternMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManuscriptPatternMatchEntity::class);
    }

    public function getAll(): array
    {
        return $this->findAll();
    }

    /**
     * @return \App\Entity\ManuscriptPatternMatchEntity[]
     */
    public function findBySourceId(int $sourceId): array
    {
        return $this->findBy(['sourceId' => $sourceId]);
    }

    /**
     * @return int[]
     */
    public function findDistinctSourceIds(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('DISTINCT m.sourceId AS sourceId')
            ->orderBy('m.sourceId', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['sourceId'], $rows);
    }

    /**
     * @return array<int, array{id:int, sourceData:string}>
     */
    public function findSourceDataBySourceIdPaginated(int $sourceId, int $limit, int $offset): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m.id AS id, m.sourceData AS sourceData')
            ->where('m.sourceId = :sourceId')
            ->setParameter('sourceId', $sourceId)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'sourceData' => (string) $row['sourceData'],
            ],
            $rows,
        );
    }
}