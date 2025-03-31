<?php

namespace App\Controller;

use App\Entity\LatvianLanguageEntity;
use App\Repository\LatvianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class LatvianLanguageController extends LanguageController
{
    #[Route('/latvian_word', name: 'get_latvian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): ?Response
    {
        /* @var LatvianLanguageRepository  $repository */
        $repository = $entityManager->getRepository(LatvianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_latvian_word']);

        if ($result) {
            /* @var LatvianLanguageEntity  $language*/
            foreach ($result as $language) {
                return new Response('id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
            }
        }

        return null;

    }

}