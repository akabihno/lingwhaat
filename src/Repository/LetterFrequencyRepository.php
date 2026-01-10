<?php

namespace App\Repository;

use App\Entity\LetterFrequencyEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LetterFrequencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LetterFrequencyEntity::class);
    }

    /**
     * Get letter frequencies for a specific language, ordered by frequency descending.
     *
     * @param string $languageCode
     * @return array Array of LetterFrequencyEntity objects
     */
    public function findByLanguageCode(string $languageCode): array
    {
        return $this->createQueryBuilder('lf')
            ->where('lf.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->orderBy('lf.frequencyScore', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get letter frequencies as an associative array [letter => score].
     *
     * @param string $languageCode
     * @return array
     */
    public function getFrequencyMapByLanguageCode(string $languageCode): array
    {
        $results = $this->createQueryBuilder('lf')
            ->select('lf.letter', 'lf.frequencyScore')
            ->where('lf.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->orderBy('lf.frequencyScore', 'DESC')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($results as $result) {
            $map[$result['letter']] = (float) $result['frequencyScore'];
        }

        return $map;
    }

    /**
     * Delete all frequency data for a specific language.
     *
     * @param string $languageCode
     * @return int Number of deleted records
     */
    public function deleteByLanguageCode(string $languageCode): int
    {
        return $this->createQueryBuilder('lf')
            ->delete()
            ->where('lf.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->execute();
    }
}
