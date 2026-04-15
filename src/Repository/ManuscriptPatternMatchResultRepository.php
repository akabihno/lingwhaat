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
        $conn = $this->getEntityManager()->getConnection();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $existing = $conn->fetchOne(
            'SELECT id FROM manuscript_pattern_match_result WHERE match_id = ?',
            [$matchId]
        );

        if ($existing === false) {
            $conn->executeStatement(
                'INSERT INTO manuscript_pattern_match_result (match_id, source_id, results, ts_created) VALUES (?, ?, ?, ?)',
                [$matchId, $sourceId, $results, $now]
            );
        } else {
            $conn->executeStatement(
                'UPDATE manuscript_pattern_match_result SET source_id = ?, results = ?, ts_created = ? WHERE match_id = ?',
                [$sourceId, $results, $now, $matchId]
            );
        }
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
