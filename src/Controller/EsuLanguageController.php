<?php

namespace App\Controller;

use App\Entity\EsuLanguageEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class EsuLanguageController extends AbstractController
{
    #[Route('/language', name: 'get_language', methods: ['GET', 'POST'])]
    public function getLanguageData(EntityManagerInterface $entityManager): Response
    {

        //$esuLanguageRepository = $entityManager->getRepository(EsuLanguageEntity::class);
        //$result = $esuLanguageRepository->findAllOrderedByName();

        return new Response($_ENV['DATABASE_URL']);
    }

}