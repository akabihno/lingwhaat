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

    public function insert(int $matchId, int $sourceId, string $results): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $conn->executeStatement(
            'INSERT INTO manuscript_pattern_match_result (match_id, source_id, results, ts_created) VALUES (?, ?, ?, ?)',
            [$matchId, $sourceId, $results, $now]
        );
    }

    /**
     * Returns rows that the scheduled scorer should process.
     *
     * Canonical-pattern-overlap rows (produced by app:canonical-pattern-stats) are excluded:
     * their results payload uses a different shape than the ES-hit JSON the scorer expects,
     * so running the scorer on them would mark them as scored=0.0 and bury them forever.
     */
    public function findUnscored(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.languageScore IS NULL')
            ->andWhere('r.results NOT LIKE :overlapPrefix')
            ->setParameter('overlapPrefix', '{"detector":"canonical_pattern_overlap"%');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
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

    /**
     * Mirror of {@see findUnscored()} for the Atbash scorer: rows whose Atbash
     * score has not yet been computed. Canonical-pattern-overlap rows are excluded
     * for the same reason as in findUnscored().
     */
    public function findUnscoredAtbash(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.languageScoreAtbash IS NULL')
            ->andWhere('r.results NOT LIKE :overlapPrefix')
            ->setParameter('overlapPrefix', '{"detector":"canonical_pattern_overlap"%');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function updateScoreAtbash(int $id, ?string $languageCodeAtbash, ?float $languageScoreAtbash): void
    {
        $entity = $this->find($id);
        if ($entity === null) {
            return;
        }

        $entity->setLanguageCodeAtbash($languageCodeAtbash)->setLanguageScoreAtbash($languageScoreAtbash);
        $this->getEntityManager()->flush();
    }
}
