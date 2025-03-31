<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LanguageDetectionController extends AbstractController
{
    const LANGUAGE_NOT_FOUND = 'Language not found';

    public function __construct(protected HttpClientInterface $httpClient, protected UrlGeneratorInterface $urlGenerator)
    {
    }
    #[Route('/language', name: 'get_language', methods: ['GET'])]
    public function process(): Response
    {
        $response = self::LANGUAGE_NOT_FOUND;

        $languageInput = $_GET['get_language'];

        if ($languageInput) {
            foreach (explode(' ', $languageInput) as $word) {
                $url = $this->urlGenerator->generate('get_esu_word', ['get_esu_word' => $word], UrlGeneratorInterface::ABSOLUTE_URL);

                $response = $this->httpClient->request('GET', $url);
            }
        }

        return $this->render('response.html.twig', [
            'response' => $response,
        ]);

    }

}