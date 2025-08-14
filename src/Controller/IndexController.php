<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{
    const string TEXT_PROMPT = 'Enter text to detect language';
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, RateLimiterFactory $anonymousApiLimiter): Response
    {
        $limiter = $anonymousApiLimiter->create($request->getClientIp());

        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->render('too_many_requests.html.twig');
        }

        return $this->render('index.html.twig', [
            'prompt' => self::TEXT_PROMPT,
        ]);
    }

}