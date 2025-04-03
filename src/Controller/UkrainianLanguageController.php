<?php

namespace App\Controller;

use App\Entity\UkrainianLanguageEntity;
use App\Repository\UkrainianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class UkrainianLanguageController extends LanguageController
{
    #[Route('/ukrainian_word', name: 'get_ukrainian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var UkrainianLanguageRepository  $repository */
        $repository = $entityManager->getRepository(UkrainianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_ukrainian_word']);

        if ($result) {
            /* @var UkrainianLanguageEntity  $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}