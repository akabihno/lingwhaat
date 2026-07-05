<?php

namespace App\Repository;

use App\Entity\ManuscriptCharacterGroupEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ManuscriptCharacterGroupEntity>
 */
class ManuscriptCharacterGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManuscriptCharacterGroupEntity::class);
    }

    /**
     * Distinct grouping sequences declared for a manuscript source, returned longest-first so that
     * greedy tokenization consumes the most specific ligature at each position.
     *
     * @return array<int, string>
     */
    public function findSequencesBySourceId(int $sourceId): array
    {
        $rows = $this->createQueryBuilder('g')
            ->select('DISTINCT g.sequence AS sequence')
            ->where('g.sourceId = :sourceId')
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->getArrayResult();

        $sequences = array_map(static fn (array $row): string => (string) $row['sequence'], $rows);
        usort($sequences, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return $sequences;
    }
}
