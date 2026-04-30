<?php

namespace App\Service;

use App\Entity\ManuscriptPatternMatchEntity;
use App\Repository\ManuscriptAlphabetDecodeResultRepository;
use App\Service\Search\CanonicalPattern;
use App\Service\Search\FuzzySearchService;

class ManuscriptAlphabetDecodeService
{
    private const int WINDOW_SIZE = 18;
    private const int MIN_WORD_LENGTH = 3;
    private const int MAX_WORD_LENGTH = 9;
    private const int MIN_WORDS_IN_SPLIT = 2;
    private const int MAX_WORDS_IN_SPLIT = 4;
    private const int MAX_WINDOW_POSITIONS = 80;
    private const int CANDIDATES_PER_SLOT = 30;

    /** @var array<int, list<list<int>>> */
    private array $splitCache = [];

    public function __construct(
        private readonly ManuscriptAlphabetDecodeResultRepository $resultRepository,
        private readonly FuzzySearchService $fuzzySearchService,
    ) {
    }

    public function decode(ManuscriptPatternMatchEntity $match, string $languageCode, int $windowSize = self::WINDOW_SIZE): int
    {
        $normalized = $this->normalize($match->getSourceData());
        $textLength = mb_strlen($normalized);

        if ($textLength < $windowSize) {
            return 0;
        }

        $splits = $this->generateWordSplits($windowSize);
        $maxPos = min($textLength - $windowSize, self::MAX_WINDOW_POSITIONS - 1);
        $patternCache = [];
        $savedCount = 0;

        for ($pos = 0; $pos <= $maxPos; $pos++) {
            $window = mb_substr($normalized, $pos, $windowSize);

            foreach ($splits as $wordLengths) {
                $row = $this->buildRow($window, $wordLengths, $languageCode, $patternCache);
                if ($row === null) {
                    continue;
                }

                $this->resultRepository->insert(
                    $match->getId(),
                    $languageCode,
                    $pos,
                    implode(',', $wordLengths),
                    $row['cipherWords'],
                    $row['wordCandidates'],
                    $row['priorityHint'],
                );
                $savedCount++;
            }
        }

        return $savedCount;
    }

    /**
     * @param array<string, list<array<string, mixed>>> $patternCache
     * @return array{cipherWords: string, wordCandidates: string, priorityHint: float}|null
     */
    private function buildRow(string $window, array $wordLengths, string $languageCode, array &$patternCache): ?array
    {
        $cipherWords = [];
        $candidatesPerSlot = [];
        $topScoreSum = 0.0;

        $charPos = 0;
        foreach ($wordLengths as $wordLen) {
            $cipherWord = mb_substr($window, $charPos, $wordLen);
            $cipherWords[] = $cipherWord;
            $charPos += $wordLen;

            $pattern = CanonicalPattern::fromString($cipherWord);
            $cacheKey = $languageCode . '|' . $pattern;

            if (!isset($patternCache[$cacheKey])) {
                $patternCache[$cacheKey] = $this->fuzzySearchService->findByPattern(
                    $pattern,
                    $languageCode,
                    self::CANDIDATES_PER_SLOT,
                );
            }

            $hits = $patternCache[$cacheKey];
            if (empty($hits)) {
                return null;
            }

            $words = [];
            $topScore = 0;
            foreach ($hits as $hit) {
                $words[] = (string) ($hit['word'] ?? '');
                $score = (int) ($hit['score'] ?? 0);
                if ($score > $topScore) {
                    $topScore = $score;
                }
            }

            $candidatesPerSlot[] = $words;
            $topScoreSum += $topScore;
        }

        return [
            'cipherWords' => implode(' ', $cipherWords),
            'wordCandidates' => json_encode($candidatesPerSlot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'priorityHint' => count($candidatesPerSlot) > 0 ? $topScoreSum / count($candidatesPerSlot) : 0.0,
        ];
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
