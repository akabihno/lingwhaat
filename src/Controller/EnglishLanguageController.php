<?php

namespace App\Controller;

use App\Entity\EnglishLanguageEntity;
use App\Repository\EnglishLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class EnglishLanguageController extends LanguageController
{
    #[Route('/english_word', name: 'get_english_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var EnglishLanguageRepository $repository*/
        $repository = $entityManager->getRepository(EnglishLanguageEntity::class);
        $result = $repository->findByName($_GET['get_english_word']);

        if ($result) {
            /* @var EnglishLanguageEntity $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}