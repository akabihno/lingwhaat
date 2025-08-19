<?php

namespace App\Controller;
use App\Service\LanguageDetection\LanguageDetectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use OpenApi\Attributes as OA;


class LanguageDetectionController extends AbstractController
{
    public function __construct(protected LanguageDetectionService $languageDetectionService)
    {
    }
    #[Route('/language', name: 'get_language', methods: ['GET'])]
    #[OA\Get(
        path: "/language",
        description: "Takes an input string and returns the detected language, ISO code, and metadata.",
        summary: "Detects the language of a given text",
        parameters: [
            new OA\Parameter(
                name: "get_language",
                description: "The text to analyze",
                in: "query",
                required: true,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "translit_detection",
                description: "Enable transliteration detection (0 = off, 1 = on)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 0, enum: [0,1])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful language detection",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "language", type: "string", example: "English"),
                        new OA\Property(property: "code", type: "string", example: "en"),
                        new OA\Property(property: "input", type: "string", example: "Hello, world!"),
                        new OA\Property(property: "time", type: "float", example: 0.0023),
                        new OA\Property(property: "count", type: "integer", example: 2),
                        new OA\Property(property: "matches", type: "integer", example: 2)
                    ]
                )
            ),
            new OA\Response(
                response: 429,
                description: "Too many requests (rate limit exceeded)"
            )
        ]
    )]
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