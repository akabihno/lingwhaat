<?php

namespace App\Controller;

use App\Entity\HindiLanguageEntity;
use App\Repository\HindiLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class HindiLanguageController extends LanguageController
{
    #[Route('/hindi_word', name: 'get_hindi_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var HindiLanguageRepository $repository*/
        $repository = $entityManager->getRepository(HindiLanguageEntity::class);
        $result = $repository->findByName($_GET['get_hindi_word']);

        if ($result) {
            /* @var HindiLanguageEntity $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}