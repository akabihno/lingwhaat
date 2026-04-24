<?php

namespace App\Repository;

use App\Entity\ManuscriptAlphabetDecodeResultEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ManuscriptAlphabetDecodeResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManuscriptAlphabetDecodeResultEntity::class);
    }

    public function insert(
        int $matchId,
        string $languageCode,
        int $windowPosition,
        string $wordLengths,
        string $decodedPhrase,
        string $wordMatches,
    ): void {
        $conn = $this->getEntityManager()->getConnection();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $conn->executeStatement(
            'INSERT INTO manuscript_alphabet_decode_result
                (match_id, language_code, window_position, word_lengths, decoded_phrase, word_matches, ts_created)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$matchId, $languageCode, $windowPosition, $wordLengths, $decodedPhrase, $wordMatches, $now]
        );
    }

    /**
     * @return ManuscriptAlphabetDecodeResultEntity[]
     */
    public function findUnscored(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.languageScore IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function updateScore(int $id, ?string $scoredLanguageCode, ?float $languageScore): void
    {
        $entity = $this->find($id);
        if ($entity === null) {
            return;
        }

        $entity->setScoredLanguageCode($scoredLanguageCode)->setLanguageScore($languageScore);
        $this->getEntityManager()->flush();
    }
}
