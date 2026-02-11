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
}
