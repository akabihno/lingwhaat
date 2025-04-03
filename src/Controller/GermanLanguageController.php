<?php

namespace App\Controller;

use App\Entity\GermanLanguageEntity;
use App\Repository\GermanLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class GermanLanguageController extends LanguageController
{
    #[Route('/german_word', name: 'get_german_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var GermanLanguageRepository  $repository */
        $repository = $entityManager->getRepository(GermanLanguageEntity::class);
        $result = $repository->findByName($_GET['get_german_word']);

        if ($result) {
            /* @var GermanLanguageEntity  $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}