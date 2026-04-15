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
