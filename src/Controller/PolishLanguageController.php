<?php

namespace App\Controller;

use App\Entity\PolishLanguageEntity;
use App\Repository\PolishLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class PolishLanguageController extends LanguageController
{
    #[Route('/polish_word', name: 'get_polish_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var PolishLanguageRepository  $repository */
        $repository = $entityManager->getRepository(PolishLanguageEntity::class);
        $result = $repository->findByName($_GET['get_polish_word']);

        if ($result) {
            /* @var PolishLanguageEntity  $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}