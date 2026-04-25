<?php

namespace App\Service;

use App\Constant\ScriptAlphabets;
use App\Entity\ManuscriptPatternMatchEntity;
use App\Repository\ManuscriptAlphabetDecodeResultRepository;
use App\Service\Cache\RedisCacheService;
use App\Service\LanguageDetection\LanguageValidation\LanguageValidationService;
use App\Service\Search\FuzzySearchService;

class ManuscriptAlphabetDecodeService
{
    private const int WINDOW_SIZE = 18;
    private const int MIN_WORD_LENGTH = 3;
    private const int MAX_WORD_LENGTH = 9;
    private const int MIN_WORDS_IN_SPLIT = 2;
    private const int MAX_WORDS_IN_SPLIT = 4;
    private const int MAX_NEW_UNIQUE_CHARS = 16;
    private const int CACHE_TTL = 3600;
    private const int MAX_WINDOW_POSITIONS = 80;

    /** @var array<int, list<list<int>>> */
    private array $splitCache = [];

    public function __construct(
        private readonly ManuscriptAlphabetDecodeResultRepository $resultRepository,
        private readonly LanguageValidationService $languageValidationService,
        private readonly FuzzySearchService $fuzzySearchService,
        private readonly RedisCacheService $cacheService,
    ) {
    }

    public function decode(ManuscriptPatternMatchEntity $match, string $languageCode, int $windowSize = self::WINDOW_SIZE): int
    {
        $normalized = $this->normalize($match->getSourceData());
        $textLength = mb_strlen($normalized);

        if ($textLength < $windowSize) {
            return 0;
        }

        $alphabet = ScriptAlphabets::getAlphabetForLanguage($languageCode);
        $alphabetChars = mb_str_split($alphabet);
        $splits = $this->generateWordSplits($windowSize);

        $maxPos = min($textLength - $windowSize, self::MAX_WINDOW_POSITIONS - 1);
        $savedCount = 0;

        for ($pos = 0; $pos <= $maxPos; $pos++) {
            $window = mb_substr($normalized, $pos, $windowSize);

            foreach ($splits as $wordLengths) {
                $savedCount += $this->backtrack(
                    $match, $languageCode, $window, $pos,
                    $wordLengths, $alphabetChars, [], 0, 0, [], []
                );
            }
        }

        return $savedCount;
    }

    private function backtrack(
        ManuscriptPatternMatchEntity $match,
        string $languageCode,
        string $window,
        int $windowPos,
        array $wordLengths,
        array $alphabetChars,
        array $mapping,
        int $wordIndex,
        int $charPos,
        array $decodedWords,
        array $wordMatches,
    ): int {
        if ($wordIndex >= count($wordLengths)) {
            $this->resultRepository->insert(
                $match->getId(),
                $languageCode,
                $windowPos,
                implode(',', $wordLengths),
                implode(' ', $decodedWords),
                json_encode($wordMatches, JSON_THROW_ON_ERROR),
            );
            return 1;
        }

        $wordLen = $wordLengths[$wordIndex];
        $cipherWord = mb_substr($window, $charPos, $wordLen);
        $cipherChars = mb_str_split($cipherWord);

        $newChars = [];
        foreach ($cipherChars as $c) {
            if (!isset($mapping[$c])) {
                $newChars[$c] = true;
            }
        }
        $newChars = array_keys($newChars);

        if (count($newChars) > self::MAX_NEW_UNIQUE_CHARS) {
            return 0;
        }

        // Bijective mapping: exclude already-used target chars
        $available = array_values(array_diff($alphabetChars, array_values($mapping)));

        $saved = 0;
        foreach ($this->generateAssignments($newChars, $available) as $assignment) {
            $newMapping = array_merge($mapping, $assignment);
            $candidate = implode('', array_map(fn($c) => $newMapping[$c], $cipherChars));

            $cacheKey = $this->buildCacheKey($languageCode, $cipherWord, $newMapping, $cipherChars);
            if ($this->cacheService->get($cacheKey) !== null) {
                continue;
            }

            $validation = $this->languageValidationService->analyze($candidate);
            if (!$validation['isNatural']) {
                $this->cacheService->set($cacheKey, 1, self::CACHE_TTL);
                continue;
            }

            $searchResults = $this->fuzzySearchService->findClosestMatches($candidate, 5, $languageCode);
            if (empty($searchResults)) {
                continue;
            }

            $saved += $this->backtrack(
                $match, $languageCode, $window, $windowPos,
                $wordLengths, $alphabetChars, $newMapping,
                $wordIndex + 1, $charPos + $wordLen,
                [...$decodedWords, $candidate],
                [...$wordMatches, [$candidate => $searchResults]],
            );
        }

        return $saved;
    }

    /**
     * Yields all bijective assignments of $chars to distinct elements from $alphabet.
     *
     * @param list<string> $chars
     * @param list<string> $alphabet
     * @return \Generator<array<string, string>>
     */
    private function generateAssignments(array $chars, array $alphabet): \Generator
    {
        if (empty($chars)) {
            yield [];
            return;
        }

        $first = $chars[0];
        $rest = array_slice($chars, 1);

        foreach ($alphabet as $i => $target) {
            $remaining = $alphabet;
            unset($remaining[$i]);

            foreach ($this->generateAssignments($rest, array_values($remaining)) as $sub) {
                yield [$first => $target] + $sub;
            }
        }
    }

    private function buildCacheKey(string $languageCode, string $cipherWord, array $mapping, array $cipherChars): string
    {
        $unique = array_unique($cipherChars);
        $relevantMap = array_intersect_key($mapping, array_flip($unique));
        ksort($relevantMap);
        return "alpha_decode_bad:{$languageCode}:{$cipherWord}:" . md5(serialize($relevantMap));
    }

    private function generateWordSplits(int $total): array
    {
        if (!isset($this->splitCache[$total])) {
            $this->splitCache[$total] = $this->doGenerateSplits($total, self::MIN_WORDS_IN_SPLIT, self::MAX_WORDS_IN_SPLIT);
        }
        return $this->splitCache[$total];
    }

    private function doGenerateSplits(int $remaining, int $minWords, int $maxWords): array
    {
        if ($remaining === 0) {
            return $minWords <= 0 ? [[]] : [];
        }
        if ($maxWords <= 0) {
            return [];
        }

        $result = [];
        for ($wordLen = self::MIN_WORD_LENGTH; $wordLen <= min(self::MAX_WORD_LENGTH, $remaining); $wordLen++) {
            foreach ($this->doGenerateSplits($remaining - $wordLen, $minWords - 1, $maxWords - 1) as $sub) {
                $result[] = array_merge([$wordLen], $sub);
            }
        }
        return $result;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
