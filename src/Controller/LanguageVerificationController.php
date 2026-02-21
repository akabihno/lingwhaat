<?php

namespace App\Controller;

use App\Service\LanguageDetection\LanguageValidation\LanguageVerificationService;
use App\Service\Logging\ElasticsearchLogger;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Language Validation')]
class LanguageVerificationController extends AbstractController
{
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageVerificationService $languageVerificationService
    )
    {
    }

    #[Route('/api/language/verify', name: 'language_verify', methods: ['POST'])]
    #[OA\Post(
        path: '/api/language/verify',
        description: 'Verifies what percentage of the input text matches a specific language using n-gram analysis and fuzzy matching. Works with obfuscated text (no spaces required). Uses top 2000 words from Elasticsearch index.',
        summary: 'Verify text language match percentage using n-grams',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['text', 'languageCode'],
                properties: [
                    new OA\Property(
                        property: 'text',
                        description: 'Input text to analyze (can be obfuscated without spaces)',
                        type: 'string',
                        example: 'привётмирэтотекстнарусском'
                    ),
                    new OA\Property(
                        property: 'languageCode',
                        description: 'Target language code to verify against',
                        type: 'string',
                        example: 'ru'
                    ),
                    new OA\Property(
                        property: 'minNgram',
                        description: 'Minimum n-gram length (default: 3)',
                        type: 'integer',
                        example: 3
                    ),
                    new OA\Property(
                        property: 'maxNgram',
                        description: 'Maximum n-gram length (default: 5)',
                        type: 'integer',
                        example: 5
                    ),
                    new OA\Property(
                        property: 'fuzziness',
                        description: 'Fuzzy matching fuzziness level 0-2 (default: 1)',
                        type: 'integer',
                        example: 1
                    )
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Language verification completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'matchPercentage',
                            description: 'Percentage of text that matches the target language (0-100)',
                            type: 'number',
                            format: 'float',
                            example: 87.5
                        ),
                        new OA\Property(
                            property: 'matchedWords',
                            description: 'Array of unique words from the language dictionary that were matched in the text',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['привет', 'мир', 'это', 'текст', 'на', 'русском']
                        ),
                        new OA\Property(
                            property: 'details',
                            description: 'Detailed analysis results',
                            properties: [
                                new OA\Property(
                                    property: 'languageCode',
                                    description: 'Target language code that was verified',
                                    type: 'string',
                                    example: 'ru'
                                ),
                                new OA\Property(
                                    property: 'textLength',
                                    description: 'Length of normalized input text',
                                    type: 'integer',
                                    example: 120
                                ),
                                new OA\Property(
                                    property: 'matchedCharacters',
                                    description: 'Number of characters matched',
                                    type: 'integer',
                                    example: 105
                                ),
                                new OA\Property(
                                    property: 'ngramsGenerated',
                                    description: 'Total number of n-grams generated from text',
                                    type: 'integer',
                                    example: 450
                                ),
                                new OA\Property(
                                    property: 'ngramsMatched',
                                    description: 'Number of n-grams that matched known words',
                                    type: 'integer',
                                    example: 380
                                ),
                                new OA\Property(
                                    property: 'matchedWordsCount',
                                    description: 'Number of unique words matched',
                                    type: 'integer',
                                    example: 42
                                ),
                                new OA\Property(
                                    property: 'minNgram',
                                    description: 'Minimum n-gram length used',
                                    type: 'integer',
                                    example: 3
                                ),
                                new OA\Property(
                                    property: 'maxNgram',
                                    description: 'Maximum n-gram length used',
                                    type: 'integer',
                                    example: 5
                                ),
                                new OA\Property(
                                    property: 'fuzziness',
                                    description: 'Fuzziness level used for matching',
                                    type: 'integer',
                                    example: 1
                                )
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - missing required parameters'
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error - verification failed'
            )
        ]
    )]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['text']) || !isset($data['languageCode'])) {
            return $this->json([
                'error' => 'Both "text" and "languageCode" are required'
            ], 400);
        }

        $text = $data['text'];
        $languageCode = $data['languageCode'];
        $minNgram = $data['minNgram'] ?? 3;
        $maxNgram = $data['maxNgram'] ?? 5;
        $fuzziness = $data['fuzziness'] ?? 1;

        try {
            $result = $this->languageVerificationService->verifyLanguage(
                $text,
                $languageCode,
                $minNgram,
                $maxNgram,
                $fuzziness
            );

            $this->logger->info(
                'Language verification request processed',
                [
                    'controller' => '[LanguageVerificationController]',
                    'languageCode' => $languageCode,
                    'textLength' => mb_strlen($text),
                    'matchPercentage' => $result['matchPercentage']
                ]
            );

            return $this->json($result);
        } catch (\Exception $e) {
            $this->logger->error(
                'Language verification failed',
                [
                    'controller' => '[LanguageVerificationController]',
                    'error' => $e->getMessage(),
                    'languageCode' => $languageCode
                ]
            );

            return $this->json([
                'error' => 'Language verification failed: ' . $e->getMessage()
            ], 500);
        }
    }
}