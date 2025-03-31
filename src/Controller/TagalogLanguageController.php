<?php

namespace App\Controller;

use App\Entity\TagalogLanguageEntity;
use App\Repository\TagalogLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class TagalogLanguageController extends LanguageController
{
    #[Route('/tagalog_word', name: 'get_tagalog_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): ?Response
    {
        /* @var TagalogLanguageRepository  $repository */
        $repository = $entityManager->getRepository(TagalogLanguageEntity::class);
        $result = $repository->findByName($_GET['get_tagalog_word']);

        if ($result) {
            /* @var TagalogLanguageEntity  $language*/
            foreach ($result as $language) {
                return new Response('id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
            }
        }

        return null;

    }

}