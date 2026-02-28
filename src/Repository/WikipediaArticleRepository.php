<?php

namespace App\Repository;

use App\Entity\WikipediaArticleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WikipediaArticleEntity>
 *
 * @method WikipediaArticleEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method WikipediaArticleEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method WikipediaArticleEntity[]    findAll()
 * @method WikipediaArticleEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WikipediaArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WikipediaArticleEntity::class);
    }

    /**
     * @return WikipediaArticleEntity[]
     */
    public function findByLanguageCodePaginated(string $languageCode, int $limit = 100, int $offset = 0): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByLanguageCode(string $languageCode): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
