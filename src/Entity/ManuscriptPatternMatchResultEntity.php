<?php

namespace App\Entity;

use App\Repository\ManuscriptPatternMatchResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManuscriptPatternMatchResultRepository::class)]
#[ORM\Table(name: "manuscript_pattern_match_result")]
class ManuscriptPatternMatchResultEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $matchId;

    #[ORM\Column(type: "integer")]
    private int $sourceId;

    #[ORM\Column(type: "text")]
    private string $results;

    #[ORM\Column(type: "string", length: 8, nullable: true)]
    private ?string $languageCode = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $languageScore = null;

    #[ORM\Column(name: "language_code_atbash", type: "string", length: 8, nullable: true)]
    private ?string $languageCodeAtbash = null;

    #[ORM\Column(name: "language_score_atbash", type: "float", nullable: true)]
    private ?float $languageScoreAtbash = null;

    #[ORM\Column(name: "ts_created", type: "string", length: 255)]
    private string $tsCreated;

    public function getId(): int
    {
        return $this->id;
    }

    public function getMatchId(): int
    {
        return $this->matchId;
    }

    public function setMatchId(int $matchId): self
    {
        $this->matchId = $matchId;
        return $this;
    }

    public function getSourceId(): int
    {
        return $this->sourceId;
    }

    public function setSourceId(int $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getResults(): string
    {
        return $this->results;
    }

    public function setResults(string $results): self
    {
        $this->results = $results;
        return $this;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(?string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getLanguageScore(): ?float
    {
        return $this->languageScore;
    }

    public function setLanguageScore(?float $languageScore): self
    {
        $this->languageScore = $languageScore;
        return $this;
    }

    public function getLanguageCodeAtbash(): ?string
    {
        return $this->languageCodeAtbash;
    }

    public function setLanguageCodeAtbash(?string $languageCodeAtbash): self
    {
        $this->languageCodeAtbash = $languageCodeAtbash;
        return $this;
    }

    public function getLanguageScoreAtbash(): ?float
    {
        return $this->languageScoreAtbash;
    }

    public function setLanguageScoreAtbash(?float $languageScoreAtbash): self
    {
        $this->languageScoreAtbash = $languageScoreAtbash;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): self
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }
}
