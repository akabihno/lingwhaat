<?php

namespace App\Repository;

use App\Entity\WikipediaPatternParseScheduleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

class WikipediaPatternParseScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');

        parent::__construct($registry, WikipediaPatternParseScheduleEntity::class);
    }

    public function getAll(): array
    {
        return $this->findAll();
    }

}