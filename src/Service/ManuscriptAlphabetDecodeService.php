<?php

namespace App\Service;

use App\Constant\ScriptAlphabets;
use App\Entity\ManuscriptPatternMatchEntity;
use App\Repository\LetterFrequencyRepository;
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
    /**
     * Hard cap on combinations tried per word per split.
     * With frequency-guided ordering, the first combination IS the classic frequency-analysis
     * mapping, so valid decryptions surface immediately. The cap prevents spending hours on
     * windows that have no valid solution.
     */
    private const int MAX_COMBINATIONS_PER_WORD = 10000;
    private const int CACHE_TTL = 3600;
    private const int MAX_WINDOW_POSITIONS = 80;
    /** @var array<int, list<list<int>>> */
    private array $splitCache = [];

    public function __construct(
        private readonly ManuscriptAlphabetDecodeResultRepository $resultRepository,
        private readonly LetterFrequencyRepository $letterFrequencyRepository,
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

        // Sort language alphabet chars by frequency descending so the generator yields
        // the classical frequency-analysis mapping as its very first combination.
        $langFreqMap = $this->letterFrequencyRepository->getFrequencyMapByLanguageCode($languageCode);
        $alphabet = ScriptAlphabets::getAlphabetForLanguage($languageCode);
        $alphabetChars = mb_str_split($alphabet);
        usort($alphabetChars, static fn($a, $b) => ($langFreqMap[$b] ?? 0.0) <=> ($langFreqMap[$a] ?? 0.0));

        // Cipher char frequencies: most-frequent cipher char will be assigned the
        // most-frequent language char first.
        $cipherFreqs = array_count_values(mb_str_split($normalized));

        // Script-specific vowel chars for the cheap pre-filter in backtrack().
        $vowelChars = mb_str_split(ScriptAlphabets::getVowelsForLanguage($languageCode));

        $splits = $this->generateWordSplits($windowSize);
        $maxPos = min($textLength - $windowSize, self::MAX_WINDOW_POSITIONS - 1);
        $savedCount = 0;

        for ($pos = 0; $pos <= $maxPos; $pos++) {
            $window = mb_substr($normalized, $pos, $windowSize);

            foreach ($splits as $wordLengths) {
                $savedCount += $this->backtrack(
                    $match, $languageCode, $window, $pos,
                    $wordLengths, $alphabetChars, $cipherFreqs, $vowelChars,
                    [], 0, 0, [], []
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
        array $cipherFreqs,
        array $vowelChars,
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

        // Order new cipher chars by frequency descending so the most informative char
        // is assigned first. This makes the generator's first output the frequency-
        // analysis candidate (most-common-cipher → most-common-language).
        usort($newChars, static fn($a, $b) => ($cipherFreqs[$b] ?? 0) <=> ($cipherFreqs[$a] ?? 0));

        // Bijective: available targets are language chars not yet used, still in frequency order.
        $available = array_values(array_diff($alphabetChars, array_values($mapping)));

        $saved = 0;
        $tried = 0;

        foreach ($this->generateAssignments($newChars, $available) as $assignment) {
            if (++$tried > self::MAX_COMBINATIONS_PER_WORD) {
                break;
            }

            $newMapping = array_merge($mapping, $assignment);
            $candidate = implode('', array_map(static fn($c) => $newMapping[$c], $cipherChars));

            // Cheap pre-filter: a word with no vowels for this script cannot be natural.
            // Works for any script — no I/O cost.
            if (!$this->hasVowel($candidate, $vowelChars)) {
                continue;
            }

            // analyze() is pure PHP — no I/O. Pass language code so it uses the
            // correct script-specific vowel set internally as well.
            $validation = $this->languageValidationService->analyze($candidate, $languageCode);
            if (!$validation['isNatural']) {
                continue;
            }

            // Redis is only consulted for the rare case: passed analyze but previously
            // failed search. This keeps Redis latency out of the hot path.
            $cacheKey = $this->buildCacheKey($languageCode, $cipherWord, $newMapping, $cipherChars);
            if ($this->cacheService->get($cacheKey) !== null) {
                continue;
            }

            $searchResults = $this->fuzzySearchService->findClosestMatches($candidate, 5, $languageCode);
            if (empty($searchResults)) {
                $this->cacheService->set($cacheKey, 1, self::CACHE_TTL);
                continue;
            }

            $saved += $this->backtrack(
                $match, $languageCode, $window, $windowPos,
                $wordLengths, $alphabetChars, $cipherFreqs, $vowelChars, $newMapping,
                $wordIndex + 1, $charPos + $wordLen,
                [...$decodedWords, $candidate],
                [...$wordMatches, [$candidate => $searchResults]],
            );
        }

        return $saved;
    }

    /**
     * Yields bijective assignments of $chars → distinct elements of $alphabet,
     * in the order that $alphabet was provided (frequency-descending from decode()).
     * The first yielded value is therefore the frequency-analysis mapping.
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

    private function hasVowel(string $word, array $vowelChars): bool
    {
        foreach (mb_str_split($word) as $char) {
            if (in_array($char, $vowelChars, true)) {
                return true;
            }
        }
        return false;
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
