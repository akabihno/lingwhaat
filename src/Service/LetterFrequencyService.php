<?php

namespace App\Service;

use App\Constant\LanguageMappings;
use App\Query\LetterFrequencyQuery;
use App\Repository\LetterFrequencyRepository;
use Psr\Log\LoggerInterface;

class LetterFrequencyService
{
    public function __construct(
        private LetterFrequencyQuery $letterFrequencyQuery,
        private LetterFrequencyRepository $letterFrequencyRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Calculate and store letter frequencies for a specific language.
     *
     * @param string $languageCode Language code
     * @return int Number of letters processed
     */
    public function calculateAndStoreFrequencies(string $languageCode): int
    {
        $this->logger->info('Calculating letter frequencies', [
            'service' => '[LetterFrequencyService]',
            'language_code' => $languageCode,
        ]);

        try {
            // Calculate frequencies from the database
            $frequencies = $this->letterFrequencyQuery->calculateLetterFrequencies($languageCode);

            if (empty($frequencies)) {
                $this->logger->warning('No frequencies calculated', [
                    'service' => '[LetterFrequencyService]',
                    'language_code' => $languageCode,
                ]);
                return 0;
            }

            // Store frequencies
            $this->letterFrequencyQuery->upsertFrequencies($languageCode, $frequencies);

            $this->logger->info('Letter frequencies calculated and stored', [
                'service' => '[LetterFrequencyService]',
                'language_code' => $languageCode,
                'letter_count' => count($frequencies),
            ]);

            return count($frequencies);
        } catch (\Exception $e) {
            $this->logger->error('Failed to calculate letter frequencies', [
                'service' => '[LetterFrequencyService]',
                'language_code' => $languageCode,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate and store letter frequencies for all languages.
     *
     * @return array Summary of results [language_code => letter_count]
     */
    public function calculateAndStoreAllLanguages(): array
    {
        $results = [];
        $languageCodes = LanguageMappings::getLanguageCodes();

        $this->logger->info('Calculating letter frequencies for all languages', [
            'service' => '[LetterFrequencyService]',
            'language_count' => count($languageCodes),
        ]);

        foreach ($languageCodes as $languageCode) {
            try {
                $letterCount = $this->calculateAndStoreFrequencies($languageCode);
                $results[$languageCode] = $letterCount;
            } catch (\Exception $e) {
                $this->logger->error('Failed to process language', [
                    'service' => '[LetterFrequencyService]',
                    'language_code' => $languageCode,
                    'error' => $e->getMessage(),
                ]);
                $results[$languageCode] = 0;
            }
        }

        $this->logger->info('Completed calculating letter frequencies for all languages', [
            'service' => '[LetterFrequencyService]',
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Get letter frequency map for a specific language.
     * Returns an associative array [letter => frequency_score].
     *
     * @param string $languageCode Language code
     * @return array
     */
    public function getFrequencyMap(string $languageCode): array
    {
        return $this->letterFrequencyRepository->getFrequencyMapByLanguageCode($languageCode);
    }

    /**
     * Create the letter_frequency table.
     */
    public function createTable(): void
    {
        $this->letterFrequencyQuery->createTable();
        $this->logger->info('Letter frequency table created', [
            'service' => '[LetterFrequencyService]',
        ]);
    }
}
