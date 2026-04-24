<?php

namespace App\Repository;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WikipediaPatternIndexOffsetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WikipediaPatternIndexOffsetEntity::class);
    }

    public function findByLanguageCode(string $languageCode): ?WikipediaPatternIndexOffsetEntity
    {
        return $this->findOneBy(['languageCode' => $languageCode]);
    }

    public function save(WikipediaPatternIndexOffsetEntity $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
}
