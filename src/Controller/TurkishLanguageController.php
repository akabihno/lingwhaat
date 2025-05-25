<?php

namespace App\Controller;

use App\Entity\TurkishLanguageEntity;
use App\Repository\TurkishLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class TurkishLanguageController extends LanguageController
{
    #[Route('/turkish_word', name: 'get_turkish_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var TurkishLanguageRepository $repository*/
        $repository = $entityManager->getRepository(TurkishLanguageEntity::class);
        $result = $repository->findByName($_GET['get_turkish_word']);

        if ($result) {
            /* @var TurkishLanguageEntity $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}