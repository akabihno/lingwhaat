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

        $candidatesPerSlot = $this->filterConsistentCandidates($cipherWords, $candidatesPerSlot);
        if ($candidatesPerSlot === null) {
            return null;
        }

        return [
            'cipherWords' => implode(' ', $cipherWords),
            'wordCandidates' => json_encode($candidatesPerSlot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'priorityHint' => count($candidatesPerSlot) > 0 ? $topScoreSum / count($candidatesPerSlot) : 0.0,
        ];
    }

    /**
     * @param list<string> $cipherWords
     * @param list<list<string>> $candidatesPerSlot
     * @return list<list<string>>|null
     */
    private function filterConsistentCandidates(array $cipherWords, array $candidatesPerSlot): ?array
    {
        $slotCount = count($cipherWords);
        $validFlags = array_fill(0, $slotCount, []);

        $this->dfsMarkValid($cipherWords, $candidatesPerSlot, 0, [], [], $validFlags);

        $result = [];
        foreach ($candidatesPerSlot as $i => $words) {
            $filtered = array_values(array_filter($words, fn($_, $idx) => isset($validFlags[$i][$idx]), ARRAY_FILTER_USE_BOTH));
            if ($filtered === []) {
                return null;
            }
            $result[] = $filtered;
        }
        return $result;
    }

    /**
     * @param list<string> $cipherWords
     * @param list<list<string>> $candidatesPerSlot
     * @param array<string, string> $mapping
     * @param array<int, int> $chosen
     * @param array<int, array<int, true>> $validFlags
     */
    private function dfsMarkValid(
        array $cipherWords,
        array $candidatesPerSlot,
        int $slot,
        array $mapping,
        array $chosen,
        array &$validFlags,
    ): bool {
        if ($slot === count($cipherWords)) {
            foreach ($chosen as $s => $idx) {
                $validFlags[$s][$idx] = true;
            }
            return true;
        }

        $anyFound = false;
        foreach ($candidatesPerSlot[$slot] as $idx => $word) {
            $wordMapping = $this->computeMapping($cipherWords[$slot], $word);
            if ($wordMapping === null || !$this->areMappingsCompatible($mapping, $wordMapping)) {
                continue;
            }
            $chosen[$slot] = $idx;
            if ($this->dfsMarkValid($cipherWords, $candidatesPerSlot, $slot + 1, $mapping + $wordMapping, $chosen, $validFlags)) {
                $anyFound = true;
            }
        }
        return $anyFound;
    }

    /** @return array<string, string>|null */
    private function computeMapping(string $cipherWord, string $targetWord): ?array
    {
        $cipherChars = str_split($cipherWord);
        $targetChars = mb_str_split($targetWord);
        if (count($cipherChars) !== count($targetChars)) {
            return null;
        }
        $mapping = [];
        foreach ($cipherChars as $i => $c) {
            $t = $targetChars[$i];
            if (isset($mapping[$c]) && $mapping[$c] !== $t) {
                return null;
            }
            $mapping[$c] = $t;
        }
        return $mapping;
    }

    /**
     * @param array<string, string> $m1
     * @param array<string, string> $m2
     */
    private function areMappingsCompatible(array $m1, array $m2): bool
    {
        foreach ($m1 as $cipher => $target) {
            if (isset($m2[$cipher]) && $m2[$cipher] !== $target) {
                return false;
            }
        }
        return true;
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
