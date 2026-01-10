<?php

namespace App\Command;

use App\Service\Search\PatternSearchService;
use App\Constant\ScriptAlphabets;
use Elastica\Client;
use Elastica\Query;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-pattern-search',
    description: 'Debug pattern search queries to understand why they return no results',
)]
class DebugPatternSearchCommand extends Command
{
    private const string INDEX_NAME = 'words_index';

    public function __construct(
        private PatternSearchService $patternSearchService,
        private Client $esClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('query-file', 'f', InputOption::VALUE_OPTIONAL, 'Path to JSON file with query')
            ->addOption('test-phrase', 'p', InputOption::VALUE_OPTIONAL, 'Russian phrase to test (space-separated words)')
            ->addOption('test-word', 'w', InputOption::VALUE_OPTIONAL, 'Single word to test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Test the specific query from the user
        $io->title('Debugging Pattern Search Query');

        // Default test case: потому что искусство поэзии требует слов
        $letterConstraints = [
            [[3], [2], [7], [], [1,7], []],
            [[], [], [5,6], [], [], [1]],
            [[], [], [1], [5,6], [], []]
        ];
        $exactLengths = [6, 3, 9, 6, 7, 4];
        $languageCode = 'ru';
        $words = ['потому', 'что', 'искусство', 'поэзии', 'требует', 'слов'];

        // Allow testing specific phrase if provided
        if ($testPhrase = $input->getOption('test-phrase')) {
            $words = explode(' ', $testPhrase);
            $exactLengths = array_map(fn($w) => mb_strlen($w, 'UTF-8'), $words);
            $io->info("Testing phrase: " . implode(' ', $words));
        }

        // Test single word if provided
        if ($testWord = $input->getOption('test-word')) {
            $this->testSingleWord($io, $testWord, $languageCode);
            return Command::SUCCESS;
        }

        $io->section('Query Parameters');
        $io->writeln("Language: $languageCode");
        $io->writeln("Words: " . implode(', ', $words));
        $io->writeln("Exact Lengths: " . implode(', ', $exactLengths));
        $io->newLine();

        // Get alphabet for Russian
        $alphabet = ScriptAlphabets::getAlphabetForLanguage($languageCode);
        $io->section('Alphabet Information');
        $io->writeln("Alphabet: $alphabet");
        $io->writeln("Alphabet length: " . mb_strlen($alphabet, 'UTF-8'));
        $io->newLine();

        // Test 1: Check if individual words exist in Elasticsearch
        $io->section('Test 1: Checking if words exist in Elasticsearch');
        foreach ($words as $index => $word) {
            $wordLength = mb_strlen($word, 'UTF-8');
            $this->checkWordExists($io, $word, $languageCode, $wordLength, $exactLengths[$index]);
        }
        $io->newLine();

        // Test 2: Analyze letter constraints for each word
        $io->section('Test 2: Analyzing Letter Constraints');
        $this->analyzeLetterConstraints($io, $words, $letterConstraints);
        $io->newLine();

        // Test 3: Test each word with its letter constraints
        $io->section('Test 3: Testing Words with Letter Constraints');
        foreach ($words as $wordIndex => $word) {
            $io->writeln("Word $wordIndex: $word (length: " . mb_strlen($word, 'UTF-8') . ")");

            // Collect constraints for this word
            $wordConstraints = [];
            foreach ($letterConstraints as $constraintIndex => $constraint) {
                if (isset($constraint[$wordIndex]) && !empty($constraint[$wordIndex])) {
                    $positions = $constraint[$wordIndex];
                    $wordConstraints[$constraintIndex] = $positions;
                    $io->writeln("  Constraint $constraintIndex: positions " . implode(', ', $positions));
                }
            }

            if (empty($wordConstraints)) {
                $io->writeln("  No constraints for this word");
                continue;
            }

            // Test each constraint individually
            foreach ($wordConstraints as $constraintIndex => $positions) {
                $expectedLetters = [];
                foreach ($positions as $pos) {
                    $letter = mb_substr($word, $pos - 1, 1, 'UTF-8');
                    $expectedLetters[] = $letter;
                }
                $uniqueLetters = array_unique($expectedLetters);

                if (count($uniqueLetters) > 1) {
                    $io->error("  Constraint $constraintIndex has different letters at positions: " .
                        implode(', ', $positions) . " => " . implode(', ', $expectedLetters));
                    continue;
                }

                $expectedLetter = $uniqueLetters[0];
                $io->writeln("  Expected letter: '$expectedLetter'");

                // Test if this constraint matches words in ES
                $this->testWordWithConstraint($io, $word, $positions, $expectedLetter,
                    $exactLengths[$wordIndex], $languageCode);
            }
            $io->newLine();
        }

        // Test 4: Test viable letters finding for each constraint
        $io->section('Test 4: Testing Viable Letters Finding');
        $alphabet = ScriptAlphabets::getAlphabetForLanguage($languageCode);

        foreach ($letterConstraints as $constraintIndex => $constraint) {
            $io->writeln("Constraint $constraintIndex:");

            // Find which word we should test (first non-empty constraint)
            $testWordIndex = null;
            $testPositions = null;
            foreach ($constraint as $wordIndex => $positions) {
                if (!empty($positions)) {
                    $testWordIndex = $wordIndex;
                    $testPositions = $positions;
                    break;
                }
            }

            if ($testWordIndex === null) {
                $io->writeln("  No positions to test");
                continue;
            }

            $io->writeln("  Testing with word $testWordIndex ({$words[$testWordIndex]}) at positions " .
                implode(', ', $testPositions));

            // Test with the actual expected letter
            $expectedLetter = mb_substr($words[$testWordIndex], $testPositions[0] - 1, 1, 'UTF-8');
            $io->writeln("  Expected letter: '$expectedLetter'");

            // Build fixedChars for this test
            $fixedChars = [];
            foreach ($testPositions as $pos) {
                $fixedChars[$pos] = $expectedLetter;
            }
            $samePositions = [array_values($testPositions)];
            $exactLength = $exactLengths[$testWordIndex] ?? null;

            try {
                $results = $this->patternSearchService->findByAdvancedPattern(
                    $samePositions,
                    $fixedChars,
                    $exactLength,
                    $languageCode,
                    5
                );

                if (!empty($results)) {
                    $io->writeln("  <info>✓ Letter '$expectedLetter' IS viable (found " . count($results) . " matches)</info>");
                } else {
                    $io->writeln("  <error>✗ Letter '$expectedLetter' is NOT viable (no matches)</error>");
                }
            } catch (\Exception $e) {
                $io->error("  Query failed: " . $e->getMessage());
            }
        }
        $io->newLine();

        // Test 5: Manually test the expected letter assignment
        $io->section('Test 5: Testing Expected Letter Assignment Manually');
        $io->writeln("Testing assignment: т, с, и");

        // For each word, build and test the query with all its constraints
        $assignedLetters = ['т', 'с', 'и'];
        $numWords = count($exactLengths);

        for ($wordIndex = 0; $wordIndex < $numWords; $wordIndex++) {
            $io->writeln("Word $wordIndex ({$words[$wordIndex]}):");

            $samePositions = [];
            $fixedChars = [];

            // Collect constraints from all letters for this word
            foreach ($letterConstraints as $constraintIndex => $constraint) {
                if (!isset($constraint[$wordIndex]) || empty($constraint[$wordIndex])) {
                    continue;
                }

                $positions = $constraint[$wordIndex];
                $letter = $assignedLetters[$constraintIndex];

                $io->writeln("  Constraint $constraintIndex: letter '$letter' at positions " . implode(', ', $positions));

                // Add to samePositions
                if (count($positions) > 0) {
                    $samePositions[] = array_values($positions);

                    // Add to fixedChars
                    foreach ($positions as $pos) {
                        $fixedChars[$pos] = $letter;
                    }
                }
            }

            if (empty($samePositions) && empty($fixedChars)) {
                $io->writeln("  <comment>No constraints for this word</comment>");
                continue;
            }

            $io->writeln("  Fixed chars: " . json_encode($fixedChars));
            $io->writeln("  Same positions: " . json_encode($samePositions));

            // Test the query
            try {
                $exactLength = $exactLengths[$wordIndex] ?? null;
                $results = $this->patternSearchService->findByAdvancedPattern(
                    $samePositions,
                    $fixedChars,
                    $exactLength,
                    $languageCode,
                    1000  // Increased limit to check if target word is in results
                );

                if (!empty($results)) {
                    $io->writeln("  <info>✓ Found " . count($results) . " total matches</info>");

                    // Check if the target word is in the results
                    $targetWord = $words[$wordIndex];
                    $foundTarget = false;
                    $targetPosition = -1;
                    foreach ($results as $index => $result) {
                        if ($result['word'] === $targetWord) {
                            $foundTarget = true;
                            $targetPosition = $index + 1;
                            break;
                        }
                    }

                    if ($foundTarget) {
                        $io->writeln("  <info>✓ Target word '$targetWord' found at position $targetPosition</info>");
                    } else {
                        $io->writeln("  <error>✗ Target word '$targetWord' NOT found in results!</error>");
                    }

                    // Show first few matches
                    $io->writeln("  First 3 matches:");
                    foreach (array_slice($results, 0, 3) as $result) {
                        $io->writeln("    - {$result['word']}");
                    }
                } else {
                    $io->writeln("  <error>✗ No matches found!</error>");
                }
            } catch (\Exception $e) {
                $io->error("  Query failed: " . $e->getMessage());
            }
        }
        $io->newLine();

        // Test 6: Simulate the exact process with assigned letters [т, с, и]
        $io->section('Test 6: Simulating Word Retrieval for Letter Assignment [т, с, и]');
        $assignedLetters = ['т', 'с', 'и'];

        $wordResults = [];
        for ($wordIndex = 0; $wordIndex < $numWords; $wordIndex++) {
            $samePositions = [];
            $fixedChars = [];

            foreach ($letterConstraints as $constraintIndex => $constraint) {
                if (!isset($constraint[$wordIndex]) || empty($constraint[$wordIndex])) {
                    continue;
                }

                $positions = $constraint[$wordIndex];
                $letter = $assignedLetters[$constraintIndex];

                if (count($positions) > 0) {
                    $samePositions[] = array_values($positions);
                    foreach ($positions as $pos) {
                        $fixedChars[$pos] = $letter;
                    }
                }
            }

            if (empty($samePositions) && empty($fixedChars)) {
                $io->writeln("Word $wordIndex: <comment>No constraints, should match many words</comment>");
                // In reality, this would search for all words of the given length
                $wordResults[$wordIndex] = ['many words...'];
                continue;
            }

            $exactLength = $exactLengths[$wordIndex] ?? null;
            try {
                $results = $this->patternSearchService->findByAdvancedPattern(
                    $samePositions,
                    $fixedChars,
                    $exactLength,
                    $languageCode,
                    500
                );

                $wordResults[$wordIndex] = $results;
                $io->writeln("Word $wordIndex ({$words[$wordIndex]}): Retrieved " . count($results) . " words");

                // Check if target word is in results
                $foundTarget = false;
                foreach ($results as $result) {
                    if ($result['word'] === $words[$wordIndex]) {
                        $foundTarget = true;
                        break;
                    }
                }

                if ($foundTarget) {
                    $io->writeln("  <info>✓ Target word '{$words[$wordIndex]}' is in results</info>");
                } else {
                    $io->writeln("  <error>✗ Target word '{$words[$wordIndex]}' NOT in results</error>");
                }
            } catch (\Exception $e) {
                $io->error("Word $wordIndex query failed: " . $e->getMessage());
                $wordResults[$wordIndex] = [];
            }
        }

        // Check if all positions have results
        $io->newLine();
        $io->writeln("Checking if all positions have results:");
        $allHaveResults = true;
        for ($wordIndex = 0; $wordIndex < $numWords; $wordIndex++) {
            if (empty($wordResults[$wordIndex])) {
                $io->writeln("  <error>✗ Word $wordIndex has NO results!</error>");
                $allHaveResults = false;
            } else {
                $io->writeln("  <info>✓ Word $wordIndex has " . count($wordResults[$wordIndex]) . " results</info>");
            }
        }

        if (!$allHaveResults) {
            $io->error("Cannot form sequences because some positions have no results!");
        } else {
            $io->success("All positions have results! Sequences can be formed.");

            // Calculate total possible combinations
            $totalCombinations = 1;
            foreach ($wordResults as $results) {
                $totalCombinations *= count($results);
            }
            $io->writeln("Total possible combinations: $totalCombinations");
        }

        $io->newLine();

        // Test 7: Check what viable letters would be found
        $io->section('Test 7: Checking Viable Letters for Each Constraint');

        foreach ($letterConstraints as $constraintIndex => $constraint) {
            $io->writeln("Constraint $constraintIndex:");

            // Find first non-empty word position
            $testWordIndex = null;
            $testPositions = null;
            foreach ($constraint as $wordIndex => $positions) {
                if (!empty($positions)) {
                    $testWordIndex = $wordIndex;
                    $testPositions = $positions;
                    break;
                }
            }

            if ($testWordIndex === null) {
                continue;
            }

            $alphabet = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
            $viableLetters = [];

            // Test all letters to see which are viable
            for ($i = 0; $i < mb_strlen($alphabet, 'UTF-8'); $i++) {
                $letter = mb_substr($alphabet, $i, 1, 'UTF-8');

                $fixedChars = [];
                foreach ($testPositions as $pos) {
                    $fixedChars[$pos] = $letter;
                }
                $samePositions = [array_values($testPositions)];
                $exactLength = $exactLengths[$testWordIndex] ?? null;

                try {
                    $results = $this->patternSearchService->findByAdvancedPattern(
                        $samePositions,
                        $fixedChars,
                        $exactLength,
                        $languageCode,
                        1
                    );

                    if (!empty($results)) {
                        $viableLetters[] = $letter;
                    }
                } catch (\Exception $e) {
                    // Ignore errors
                }
            }

            $expectedLetter = mb_substr($words[$testWordIndex], $testPositions[0] - 1, 1, 'UTF-8');
            $io->writeln("  Total viable letters found: " . count($viableLetters));
            $io->writeln("  Viable letters: [" . implode(', ', $viableLetters) . "]");
            $io->writeln("  Expected letter: '$expectedLetter'");
            if (in_array($expectedLetter, $viableLetters)) {
                $expectedPosition = array_search($expectedLetter, $viableLetters) + 1;
                $io->writeln("  <info>✓ Expected letter IS viable (position $expectedPosition of " . count($viableLetters) . ")</info>");
            } else {
                $io->writeln("  <error>✗ Expected letter is NOT viable</error>");
            }
        }

        $io->newLine();

        // Test 8: Run the full multi-letter query
        $io->section('Test 8: Running Full Multi-Letter Query');
        try {
            $results = $this->patternSearchService->findByMultiLetterSequencePattern(
                $letterConstraints,
                $exactLengths,
                $languageCode,
                100
            );

            $io->success("Query completed!");
            $io->writeln("Results count: " . count($results));

            if (!empty($results)) {
                foreach ($results as $langGroup) {
                    $io->writeln("Language: " . $langGroup['languageCode']);
                    $io->writeln("Sequences: " . count($langGroup['sequences']));

                    // Show first few results
                    $sequences = array_slice($langGroup['sequences'], 0, 3);
                    foreach ($sequences as $seq) {
                        $words = array_map(fn($w) => $w['word'], $seq['words']);
                        $letters = $seq['letters'] ?? [];
                        $io->writeln("  Letters: [" . implode(', ', $letters) . "] => " . implode(' ', $words));
                    }
                }
            } else {
                $io->warning("No results found!");
            }
        } catch (\Exception $e) {
            $io->error("Query failed: " . $e->getMessage());
            $io->writeln($e->getTraceAsString());
        }

        return Command::SUCCESS;
    }

    private function checkWordExists(SymfonyStyle $io, string $word, string $lang, int $actualLength, int $expectedLength): void
    {
        $io->write("Checking word '$word' (actual length: $actualLength, expected: $expectedLength): ");

        if ($actualLength !== $expectedLength) {
            $io->writeln("<error>LENGTH MISMATCH! Actual: $actualLength, Expected: $expectedLength</error>");
            return;
        }

        try {
            // Simple term query
            $query = new Query\BoolQuery();

            $termQuery = new Query\Term();
            $termQuery->setTerm('word', strtolower($word));
            $query->addMust($termQuery);

            $langQuery = new Query\Term();
            $langQuery->setTerm('languageCode', $lang);
            $query->addMust($langQuery);

            $mainQuery = new Query($query);
            $mainQuery->setSize(5);

            $results = $this->esClient->getIndex(self::INDEX_NAME)->search($mainQuery);
            $totalHits = $results->getTotalHits();

            if ($totalHits > 0) {
                $io->writeln("<info>FOUND ($totalHits matches)</info>");
                foreach ($results->getResults() as $result) {
                    $source = $result->getSource();
                    $io->writeln("    => {$source['word']} (lang: {$source['languageCode']})");
                }
            } else {
                $io->writeln("<error>NOT FOUND</error>");
            }
        } catch (\Exception $e) {
            $io->writeln("<error>ERROR: " . $e->getMessage() . "</error>");
        }
    }

    private function analyzeLetterConstraints(SymfonyStyle $io, array $words, array $letterConstraints): void
    {
        $io->writeln("Number of letter constraints: " . count($letterConstraints));

        foreach ($letterConstraints as $constraintIndex => $constraint) {
            $io->writeln("Constraint $constraintIndex:");

            $lettersByPosition = [];
            foreach ($constraint as $wordIndex => $positions) {
                if (empty($positions)) {
                    $io->writeln("  Word $wordIndex: no constraint");
                    continue;
                }

                $word = $words[$wordIndex];
                $letters = [];
                foreach ($positions as $pos) {
                    $letter = mb_substr($word, $pos - 1, 1, 'UTF-8');
                    $letters[] = "$letter(pos $pos)";
                    $lettersByPosition[] = $letter;
                }
                $io->writeln("  Word $wordIndex ({$words[$wordIndex]}): " . implode(', ', $letters));
            }

            $uniqueLetters = array_unique($lettersByPosition);
            if (count($uniqueLetters) > 1) {
                $io->error("  ERROR: This constraint has DIFFERENT letters across words: " .
                    implode(', ', $uniqueLetters));
            } else if (count($uniqueLetters) === 1) {
                $io->success("  OK: All positions have the same letter: '{$uniqueLetters[0]}'");
            }
        }
    }

    private function testWordWithConstraint(
        SymfonyStyle $io,
        string $word,
        array $positions,
        string $expectedLetter,
        int $exactLength,
        string $languageCode
    ): void {
        try {
            // Build the same query that PatternSearchService would build
            $fixedChars = [];
            foreach ($positions as $pos) {
                $fixedChars[$pos] = $expectedLetter;
            }
            $samePositions = [array_values($positions)];

            $results = $this->patternSearchService->findByAdvancedPattern(
                $samePositions,
                $fixedChars,
                $exactLength,
                $languageCode,
                10
            );

            if (!empty($results)) {
                $io->writeln("    <info>Found " . count($results) . " matching words:</info>");
                foreach (array_slice($results, 0, 5) as $result) {
                    $io->writeln("      - {$result['word']}");
                }
            } else {
                $io->writeln("    <error>No matching words found</error>");

                // Try without fixedChars to see if samePositions works
                $results2 = $this->patternSearchService->findByAdvancedPattern(
                    $samePositions,
                    [],
                    $exactLength,
                    $languageCode,
                    5
                );

                if (!empty($results2)) {
                    $io->writeln("    <comment>But found " . count($results2) .
                        " words when not enforcing the letter (just same positions):</comment>");
                    foreach (array_slice($results2, 0, 3) as $result) {
                        $io->writeln("      - {$result['word']}");
                    }
                }
            }
        } catch (\Exception $e) {
            $io->error("    Query failed: " . $e->getMessage());
        }
    }

    private function testSingleWord(SymfonyStyle $io, string $word, string $languageCode): void
    {
        $io->title("Testing Single Word: '$word'");

        $wordLength = mb_strlen($word, 'UTF-8');
        $io->writeln("Word length: $wordLength");

        // Test 1: Check if word exists
        $this->checkWordExists($io, $word, $languageCode, $wordLength, $wordLength);

        // Test 2: Try pattern search with exact match
        $io->section('Test 2: Pattern Search (all positions fixed)');
        $fixedChars = [];
        for ($i = 1; $i <= $wordLength; $i++) {
            $letter = mb_substr($word, $i - 1, 1, 'UTF-8');
            $fixedChars[$i] = $letter;
        }

        try {
            $results = $this->patternSearchService->findByAdvancedPattern(
                [],
                $fixedChars,
                $wordLength,
                $languageCode,
                10
            );

            if (!empty($results)) {
                $io->success("Found " . count($results) . " matches:");
                foreach ($results as $result) {
                    $io->writeln("  - {$result['word']}");
                }
            } else {
                $io->error("No matches found with pattern search");
            }
        } catch (\Exception $e) {
            $io->error("Query failed: " . $e->getMessage());
        }

        // Test 3: Check character by character
        $io->section('Test 3: Character Analysis');
        for ($i = 0; $i < $wordLength; $i++) {
            $letter = mb_substr($word, $i, 1, 'UTF-8');
            $pos = $i + 1;
            $io->writeln("Position $pos: '$letter' (Unicode: U+" . dechex(mb_ord($letter, 'UTF-8')) . ")");
        }
    }
}
