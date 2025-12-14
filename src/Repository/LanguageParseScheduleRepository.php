<?php

namespace App\Repository;

use App\Entity\LanguageParseScheduleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

class LanguageParseScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');

        parent::__construct($registry, LanguageParseScheduleEntity::class);
    }

    public function getAll(): array
    {
        return $this->findAll();
    }

}