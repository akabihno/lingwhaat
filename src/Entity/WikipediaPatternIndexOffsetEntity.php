<?php

namespace App\Entity;

use App\Repository\WikipediaPatternIndexOffsetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WikipediaPatternIndexOffsetRepository::class)]
#[ORM\Table(name: "wikipedia_pattern_index_offset")]
#[ORM\UniqueConstraint(name: "uq_language_code", columns: ["language_code"])]
class WikipediaPatternIndexOffsetEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "language_code", type: "string", length: 8)]
    private string $languageCode;

    #[ORM\Column(name: "current_offset", type: "integer")]
    private int $currentOffset = 0;

    #[ORM\Column(name: "window_size", type: "integer")]
    private int $windowSize;

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

    public function getCurrentOffset(): int
    {
        return $this->currentOffset;
    }

    public function setCurrentOffset(int $currentOffset): self
    {
        $this->currentOffset = $currentOffset;
        return $this;
    }

    public function getWindowSize(): int
    {
        return $this->windowSize;
    }

    public function setWindowSize(int $windowSize): self
    {
        $this->windowSize = $windowSize;
        return $this;
    }
}
