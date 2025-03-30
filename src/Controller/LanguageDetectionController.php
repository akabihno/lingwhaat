<?php

namespace App\Controller;

use App\Entity\EsuLanguageEntity;
use App\Repository\EsuLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';

class LanguageDetectionController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected EsuLanguageEntity $esuLanguageEntity,
        protected $esuLanguageRepository,
    )
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');

        $this->esuLanguageRepository = $entityManager->getRepository(EsuLanguageEntity::class);
    }

    #[Route('/language', name: 'get_language', methods: ['GET'])]
    public function getLanguageData(): Response
    {
        $result = $this->esuLanguageRepository->findByName('uluaq');

        /* @var EsuLanguageEntity  $language*/
        foreach ($result as $language) {
            return new Response($_GET['language'] . 'id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
        }
    }

}