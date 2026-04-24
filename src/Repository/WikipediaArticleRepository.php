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

    /**
     * @return array<int, array{id:int, text:string}>
     */
    public function findIdAndTextByLanguageCodePaginated(
        string $languageCode,
        int $limit = 100,
        int $offset = 0
    ): array {
        $rows = $this->createQueryBuilder('w')
            ->select('w.id AS id, w.text AS text')
            ->where('w.languageCode = :languageCode')
            ->setParameter('languageCode', $languageCode)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'text' => (string) $row['text'],
            ],
            $rows
        );
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

    /**
     * @return string[]
     */
    public function getDistinctLanguageCodes(): array
    {
        $rows = $this->createQueryBuilder('w')
            ->select('DISTINCT w.languageCode')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'languageCode');
    }
}
