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
     * Grouping sequences declared for a manuscript source, normalized identically to the manuscript
     * and corpus text (lowercase, letters only), de-duplicated, and returned longest-first so greedy
     * tokenization consumes the most specific ligature at each position.
     *
     * Normalization here is essential: the tokenizer runs against already-normalized text, so a row
     * stored as 'TH' or 't-h' would otherwise never match and the feature would appear enabled but do
     * nothing. Sequences that normalize to empty are dropped.
     *
     * @return list<string>
     */
    public function findSequencesBySourceId(int $sourceId): array
    {
        $rows = $this->createQueryBuilder('g')
            ->select('DISTINCT g.sequence AS sequence')
            ->where('g.sourceId = :sourceId')
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->getArrayResult();

        $unique = [];
        foreach ($rows as $row) {
            $normalized = mb_strtolower((string) $row['sequence']);
            $normalized = preg_replace('/[^\p{L}]+/u', '', $normalized) ?? '';
            if ($normalized !== '') {
                $unique[$normalized] = true;
            }
        }

        $sequences = array_keys($unique);
        usort($sequences, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return $sequences;
    }
}
