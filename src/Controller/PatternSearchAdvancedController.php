<?php

namespace App\Controller;

use App\Service\Search\PatternSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use OpenApi\Attributes as OA;

class PatternSearchAdvancedController extends AbstractController
{
    public function __construct(
        protected PatternSearchService $patternSearchService,
    ) {
    }

    #[Route('/api/pattern-search-advanced', name: 'pattern_search_advanced', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(
                ['error' => 'Invalid JSON payload: ' . json_last_error_msg()],
                Response::HTTP_BAD_REQUEST
            );
        }

        $limit = (int) ($data['limit'] ?? 100);

        if ($limit < 1 || $limit > 1000) {
            return new JsonResponse(
                ['error' => 'Limit must be between 1 and 1000'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (isset($data['intersections']) && is_array($data['intersections'])) {
            return $this->handleIntersectionSearch($data['intersections'], $limit);
        }

        $samePositions = $data['samePositions'] ?? [];
        $fixedChars = $data['fixedChars'] ?? [];
        $exactLength = isset($data['exactLength']) ? (int) $data['exactLength'] : null;
        $languageCode = $data['languageCode'] ?? null;

        if (empty($samePositions) && empty($fixedChars)) {
            return new JsonResponse(
                ['error' => 'Either samePositions, fixedChars, or intersections must be provided'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_array($samePositions)) {
            return new JsonResponse(
                ['error' => 'samePositions must be an array'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!is_array($fixedChars)) {
            return new JsonResponse(
                ['error' => 'fixedChars must be an object/array'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($exactLength !== null && $exactLength < 1) {
            return new JsonResponse(
                ['error' => 'exactLength must be a positive integer'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $results = $this->patternSearchService->findByAdvancedPattern(
                $samePositions,
                $fixedChars,
                $exactLength,
                $languageCode,
                $limit
            );

            return new JsonResponse($results, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred during pattern search: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle intersection search request.
     *
     * @param array $intersections Array of pattern configurations
     * @param int $limit Maximum number of results
     * @return JsonResponse
     */
    private function handleIntersectionSearch(array $intersections, int $limit): JsonResponse
    {
        if (empty($intersections)) {
            return new JsonResponse(
                ['error' => 'intersections array cannot be empty'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (count($intersections) < 2) {
            return new JsonResponse(
                ['error' => 'At least 2 patterns are required for intersection search'],
                Response::HTTP_BAD_REQUEST
            );
        }

        foreach ($intersections as $index => $pattern) {
            if (!is_array($pattern)) {
                return new JsonResponse(
                    ['error' => "Pattern at index $index must be an array/object"],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $samePositions = $pattern['samePositions'] ?? [];
            $fixedChars = $pattern['fixedChars'] ?? [];

            if (empty($samePositions) && empty($fixedChars)) {
                return new JsonResponse(
                    ['error' => "Pattern at index $index must have either samePositions or fixedChars"],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if (isset($pattern['exactLength']) && $pattern['exactLength'] < 1) {
                return new JsonResponse(
                    ['error' => "exactLength at pattern index $index must be a positive integer"],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        try {
            $results = $this->patternSearchService->findIntersections($intersections, $limit);

            return new JsonResponse($results, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred during intersection search: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}