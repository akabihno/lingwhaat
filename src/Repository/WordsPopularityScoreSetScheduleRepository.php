<?php

namespace App\Repository;

use App\Entity\WordsPopularityScoreSetScheduleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

class WordsPopularityScoreSetScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');

        parent::__construct($registry, WordsPopularityScoreSetScheduleEntity::class);
    }

    public function getAll(): array
    {
        return $this->findAll();
    }

    public function incrementOffsetByLanguageCode(string $languageCode, int $increment): void
    {
        $this->createQueryBuilder('b')
            ->update()
            ->set('b.offset', 'b.offset + :increment')
            ->where('b.languageCode = :languageCode')
            ->setParameter('increment', $increment)
            ->setParameter('languageCode', $languageCode)
            ->getQuery()
            ->execute();
    }

}