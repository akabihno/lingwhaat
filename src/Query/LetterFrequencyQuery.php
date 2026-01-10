<?php

namespace App\Query;

class LetterFrequencyQuery extends AbstractQuery
{
    /**
     * Create the letter_frequency table.
     */
    public function createTable(): void
    {
        $this->connect();

        $query = '
            CREATE TABLE IF NOT EXISTS letter_frequency (
                id INT(10) AUTO_INCREMENT PRIMARY KEY,
                language_code VARCHAR(10) NOT NULL,
                letter VARCHAR(10) NOT NULL,
                frequency_score FLOAT NOT NULL,
                ts_created TIMESTAMP DEFAULT NOW(),
                INDEX idx_language_code (language_code),
                INDEX idx_language_frequency (language_code, frequency_score),
                UNIQUE KEY unique_language_letter (language_code, letter)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }

    /**
     * Drop the letter_frequency table.
     */
    public function dropTable(): void
    {
        $this->connect();

        $query = 'DROP TABLE IF EXISTS letter_frequency';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }

    /**
     * Calculate letter frequencies for a specific language.
     *
     * @param string $languageCode Language code (e.g., 'ru', 'en')
     * @return array Array of [letter => frequency_score]
     */
    public function calculateLetterFrequencies(string $languageCode): array
    {
        $this->connect();

        $tableName = $this->getBaseTable($languageCode);

        // Query to calculate letter frequencies
        $query = "
            SELECT
                letter,
                (letter_count * 100.0 / total_letters) AS frequency_score
            FROM (
                SELECT
                    LOWER(SUBSTRING(name, numbers.n, 1)) AS letter,
                    COUNT(*) AS letter_count,
                    (SELECT SUM(CHAR_LENGTH(name)) FROM {$tableName}) AS total_letters
                FROM {$tableName}
                JOIN (
                    SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                    UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
                    UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
                    UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
                    UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
                ) numbers
                WHERE numbers.n <= CHAR_LENGTH(name)
                AND SUBSTRING(name, numbers.n, 1) REGEXP '[[:alpha:]]'
                GROUP BY letter
            ) AS letter_stats
            ORDER BY frequency_score DESC
        ";

        return $this->fetch($query);
    }

    /**
     * Insert or update letter frequencies for a language.
     *
     * @param string $languageCode Language code
     * @param array $frequencies Array of [letter => frequency_score]
     */
    public function upsertFrequencies(string $languageCode, array $frequencies): void
    {
        if (empty($frequencies)) {
            return;
        }

        $this->connect();

        // Delete existing frequencies for this language
        $deleteQuery = 'DELETE FROM letter_frequency WHERE language_code = :language_code';
        $stmt = $this->pdo->prepare($deleteQuery);
        $stmt->execute([':language_code' => $languageCode]);

        // Prepare bulk insert
        $valuePlaceholders = [];
        $params = [];

        foreach ($frequencies as $index => $frequency) {
            $letterParam = ":letter{$index}";
            $scoreParam = ":score{$index}";
            $langParam = ":lang{$index}";

            $valuePlaceholders[] = "({$langParam}, {$letterParam}, {$scoreParam})";

            $params[$langParam] = $languageCode;
            $params[$letterParam] = $frequency['letter'];
            $params[$scoreParam] = $frequency['frequency_score'];
        }

        $valuesClause = implode(', ', $valuePlaceholders);

        $insertQuery = "
            INSERT INTO letter_frequency (language_code, letter, frequency_score)
            VALUES {$valuesClause}
        ";

        $stmt = $this->pdo->prepare($insertQuery);
        $stmt->execute($params);
    }

    /**
     * Get letter frequencies for a specific language.
     *
     * @param string $languageCode Language code
     * @return array Array of records
     */
    public function getFrequencies(string $languageCode): array
    {
        $this->connect();

        $query = '
            SELECT letter, frequency_score
            FROM letter_frequency
            WHERE language_code = :language_code
            ORDER BY frequency_score DESC
        ';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':language_code' => $languageCode]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
