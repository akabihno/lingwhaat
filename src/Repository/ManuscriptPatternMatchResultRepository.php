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
        // Raw UPDATE rather than load-entity + flush. The scorer can hold the result entity
        // for 10-30 minutes (per-row score() is ES-heavy) before persisting; across that window
        // the long-lived worker's UnitOfWork state proved unreliable — flush() returned without
        // error yet never wrote the row (rows stayed language_score IS NULL forever). A direct
        // statement on the connection sidesteps the EM entirely and always commits.
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE manuscript_pattern_match_result SET language_code = ?, language_score = ? WHERE id = ?',
            [$languageCode, $languageScore, $id]
        );
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
        // Raw UPDATE for the same reason as updateScore(): the long-lived Atbash worker's
        // flush() after a multi-minute score() silently failed to persist. See updateScore().
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE manuscript_pattern_match_result SET language_code_atbash = ?, language_score_atbash = ? WHERE id = ?',
            [$languageCodeAtbash, $languageScoreAtbash, $id]
        );
    }
}
