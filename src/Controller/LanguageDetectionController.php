<?php

namespace App\Controller;
use App\Service\LanguageDetection\LanguageDetectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
            return $this->render('too_many_requests.html.twig');
        }

        $translitDetection = $request->query->get('translit_detection', 0);

        $languageAndCode = $this->languageDetectionService->process(
            $request->query->get('get_language'),
            $translitDetection
        );

        return $this->render('response.html.twig', [
            'language' => $languageAndCode['language'],
            'code' => $languageAndCode['code'],
            'input' => $languageAndCode['input'],
            'time' => $languageAndCode['time'],
            'count' => $languageAndCode['count'],
            'matches' => $languageAndCode['matches']
        ]);
    }



}