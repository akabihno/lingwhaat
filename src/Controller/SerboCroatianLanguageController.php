<?php

namespace App\Controller;

use App\Entity\SerboCroatianLanguageEntity;
use App\Repository\SerboCroatianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class SerboCroatianLanguageController extends LanguageController
{
    #[Route('/serbocroatian_word', name: 'get_serbocroatian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var SerboCroatianLanguageRepository  $repository */
        $repository = $entityManager->getRepository(SerboCroatianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_serbocroatian_word']);

        if ($result) {
            /* @var SerboCroatianLanguageEntity  $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}