<?php

namespace App\Entity;

use App\Repository\WordsPopularityScoreSetOffsetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordsPopularityScoreSetOffsetRepository::class)]
#[ORM\Table(name: "words_popularity_score_set_offset")]
#[ORM\UniqueConstraint(name: "uq_words_popularity_offset_language_code", columns: ["language_code"])]
class WordsPopularityScoreSetOffsetEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "language_code", type: "string", length: 8)]
    private string $languageCode;

    // Article pagination cursor for this language. Advanced by the batch limit after each run
    // (see WordsPopularityScoreSetService). Reset to 0 to restart a full pass.
    #[ORM\Column(name: "offset", type: "bigint")]
    private int $offset = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
}
