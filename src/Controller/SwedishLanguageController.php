<?php

namespace App\Controller;

use App\Entity\SwedishLanguageEntity;
use App\Repository\SwedishLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class SwedishLanguageController extends LanguageController
{
    #[Route('/swedish_word', name: 'get_swedish_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var SwedishLanguageRepository $repository*/
        $repository = $entityManager->getRepository(SwedishLanguageEntity::class);
        $result = $repository->findByName($_GET['get_swedish_word']);

        if ($result) {
            /* @var SwedishLanguageEntity $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}