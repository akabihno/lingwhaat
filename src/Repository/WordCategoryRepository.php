<?php

namespace App\Repository;

use App\Entity\WordCategoryEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WordCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordCategoryEntity::class);
    }

    public function findByLanguageCodeAndWord(string $languageCode, string $word): ?WordCategoryEntity
    {
        return $this->createQueryBuilder('wc')
            ->where('wc.languageCode = :languageCode')
            ->andWhere('wc.word = :word')
            ->setParameter('languageCode', $languageCode)
            ->setParameter('word', $word)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return WordCategoryEntity[]
     */
    public function findByLanguageCode(string $languageCode, int $limit = 1000, int $offset = 0): array
    {
        return $this->createQueryBuilder('wc')
            ->where('wc.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByLanguageCode(string $languageCode): int
    {
        return (int) $this->createQueryBuilder('wc')
            ->select('COUNT(wc.id)')
            ->where('wc.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function upsert(WordCategoryEntity $entity): WordCategoryEntity
    {
        $em = $this->getEntityManager();
        $existing = $this->findByLanguageCodeAndWord(
            $entity->getLanguageCode(),
            $entity->getWord()
        );

        if ($existing !== null) {
            $existing->setCategories($entity->getCategories());
            $existing->setTsUpdated(new \DateTime());
            $em->flush();
            return $existing;
        }

        $em->persist($entity);
        $em->flush();
        return $entity;
    }
}
