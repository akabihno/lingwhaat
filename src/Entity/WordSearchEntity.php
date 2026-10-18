<?php

namespace App\Entity;

use App\Repository\WordSearchRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One dictionary entry from a pronunciation_<language>_language table, copied into a single
 * cross-language table for search (replacing the Elasticsearch words_index). Keyed by the source
 * row, so rebuilds and score syncs address the same entry the source table does. Rows are
 * written in bulk with SQL by {@see \App\Service\Search\WordSearchIndexer}; the entity exists so
 * Doctrine's schema tooling knows the table.
 *
 * utf8mb4_bin throughout: word/token matching must be exact, like Elasticsearch's term queries
 * (the source tables use accent- and case-insensitive collations).
 */
#[ORM\Entity(repositoryClass: WordSearchRepository::class)]
#[ORM\Table(name: "word_search", options: ["charset" => "utf8mb4", "collate" => "utf8mb4_bin"])]
#[ORM\Index(name: "idx_ws_pattern", columns: ["language_code", "pattern_crc", "score"])]
#[ORM\Index(name: "idx_ws_score", columns: ["language_code", "score"])]
#[ORM\Index(name: "idx_ws_length", columns: ["language_code", "word_length"])]
class WordSearchEntity
{
    #[ORM\Id]
    #[ORM\Column(name: "language_code", length: 16)]
    private string $languageCode;

    // id of the row in the language's pronunciation table
    #[ORM\Id]
    #[ORM\Column(name: "source_id", type: "integer")]
    private int $sourceId;

    #[ORM\Column(length: 256)]
    private string $word;

    // As stored in the source table, placeholders ("Not available") included.
    #[ORM\Column(length: 255)]
    private string $ipa;

    // CanonicalPattern of the lowercased word; looked up through pattern_crc.
    #[ORM\Column(length: 1100)]
    private string $pattern;

    #[ORM\Column(name: "pattern_crc", type: "integer", options: ["unsigned" => true])]
    private int $patternCrc;

    // mb_strlen of the word as stored (not lowercased)
    #[ORM\Column(name: "word_length", type: "smallint", options: ["unsigned" => true])]
    private int $wordLength;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $score = 0;

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getSourceId(): int
    {
        return $this->sourceId;
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getPatternCrc(): int
    {
        return $this->patternCrc;
    }

    public function getWordLength(): int
    {
        return $this->wordLength;
    }

    public function getScore(): int
    {
        return $this->score;
    }
}
