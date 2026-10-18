<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A search token of a {@see WordSearchEntity}'s word or IPA, as the Elasticsearch standard
 * analyzer produced them ({@see \App\Service\Search\WordTokenizer}). Exact and wildcard lookups
 * go through the primary key; fuzzy lookups scan a (first character, length) bucket.
 * Each token is stored once per entry and field.
 */
#[ORM\Entity]
#[ORM\Table(name: "word_search_token", options: ["charset" => "utf8mb4", "collate" => "utf8mb4_bin"])]
#[ORM\Index(name: "idx_wst_bucket", columns: ["field", "token_prefix", "token_length"])]
#[ORM\Index(name: "idx_wst_lang_bucket", columns: ["field", "language_code", "token_prefix", "token_length"])]
class WordSearchTokenEntity
{
    public const int FIELD_WORD = 1;
    public const int FIELD_IPA = 2;

    #[ORM\Id]
    #[ORM\Column(type: "smallint", options: ["unsigned" => true])]
    private int $field;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $token;

    #[ORM\Id]
    #[ORM\Column(name: "language_code", length: 16)]
    private string $languageCode;

    #[ORM\Id]
    #[ORM\Column(name: "source_id", type: "integer")]
    private int $sourceId;

    // first character of the token
    #[ORM\Column(name: "token_prefix", length: 1)]
    private string $tokenPrefix;

    // token length in characters
    #[ORM\Column(name: "token_length", type: "smallint", options: ["unsigned" => true])]
    private int $tokenLength;

    public function getField(): int
    {
        return $this->field;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getSourceId(): int
    {
        return $this->sourceId;
    }

    public function getTokenPrefix(): string
    {
        return $this->tokenPrefix;
    }

    public function getTokenLength(): int
    {
        return $this->tokenLength;
    }
}
