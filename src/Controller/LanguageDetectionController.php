<?php

namespace App\Controller;
use App\Service\LanguageDetection\LanguageDetectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;


class LanguageDetectionController extends AbstractController
{
    public function __construct(protected LanguageDetectionService $languageDetectionService)
    {
    }
    #[Route('/language', name: 'get_language', methods: ['GET'])]
    public function run(Request $request, RateLimiterFactory $anonymousApiLimiter): Response
    {
        $limiter = $anonymousApiLimiter->create($request->getClientIp());

        if (false === $limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException();
        }

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