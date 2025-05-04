<?php

namespace App\Controller;
use App\Service\LanguageDetection\LanguageDetectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class LanguageDetectionController extends AbstractController
{
    public function __construct(protected LanguageDetectionService $languageDetectionService)
    {
    }
    #[Route('/language', name: 'get_language', methods: ['GET'])]
    public function run(): Response
    {
        $languageAndCode = $this->languageDetectionService->process($_GET['get_language']);

        return $this->render('response.html.twig', [
            'language' => $languageAndCode['language'],
            'code' => $languageAndCode['code'],
            'time' => $languageAndCode['time'],
            'count' => $languageAndCode['count'],
            'matches' => $languageAndCode['matches']
        ]);
    }



}