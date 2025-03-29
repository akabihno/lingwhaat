<?php

namespace App\Controller;

use App\Entity\EsuLanguageEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';

class EsuLanguageController extends AbstractController
{
    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');
    }

    #[Route('/language', name: 'get_language', methods: ['GET'])]
    public function getLanguageData(EntityManagerInterface $entityManager): Response
    {

        $esuLanguageRepository = $entityManager->getRepository(EsuLanguageEntity::class);
        $result = $esuLanguageRepository->findAllOrderedByName();

        return new Response(json_encode($result));
    }

}