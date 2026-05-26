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
        string $cipherWords,
        string $wordCandidates,
        float $priorityHint,
    ): void {
        $conn = $this->getEntityManager()->getConnection();
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $conn->executeStatement(
            'INSERT INTO manuscript_alphabet_decode_result
                (match_id, language_code, window_position, word_lengths, cipher_words, word_candidates, priority_hint, ts_created)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$matchId, $languageCode, $windowPosition, $wordLengths, $cipherWords, $wordCandidates, $priorityHint, $now]
        );
    }

    /**
     * @return ManuscriptAlphabetDecodeResultEntity[]
     */
    public function findUnprocessed(int $limit): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.openaiStatus IS NULL')
            ->orderBy('r.priorityHint', 'DESC')
            ->addOrderBy('r.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function updateSelection(int $id, string $status, ?string $selectedPhrase): void
    {
        $entity = $this->find($id);
        if ($entity === null) {
            return;
        }

        $entity->setOpenaiStatus($status)->setSelectedPhrase($selectedPhrase);
        $this->getEntityManager()->flush();
    }
}
