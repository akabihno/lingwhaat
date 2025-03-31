<?php

namespace App\Controller;

use App\Entity\EsuLanguageEntity;
use App\Repository\EsuLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class EsuLanguageController extends LanguageController
{
    #[Route('/esu_word', name: 'get_esu_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var EsuLanguageRepository  $esuLanguageRepository*/
        $esuLanguageRepository = $entityManager->getRepository(EsuLanguageEntity::class);
        $result = $esuLanguageRepository->findByName($_GET['get_esu_word']);

        /* @var EsuLanguageEntity  $language*/
        foreach ($result as $language) {
            return new Response($_GET['get_esu_word'] . ' id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
        }
    }

}