<?php

namespace App\Entity;

use App\Repository\ManuscriptAlphabetDecodeResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManuscriptAlphabetDecodeResultRepository::class)]
#[ORM\Table(name: "manuscript_alphabet_decode_result")]
#[ORM\Index(columns: ["language_score"], name: "idx_decode_unscored")]
class ManuscriptAlphabetDecodeResultEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $matchId;

    #[ORM\Column(type: "string", length: 8)]
    private string $languageCode;

    #[ORM\Column(type: "integer")]
    private int $windowPosition;

    #[ORM\Column(type: "string", length: 64)]
    private string $wordLengths;

    #[ORM\Column(type: "text")]
    private string $decodedPhrase;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $wordMatches = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $languageScore = null;

    #[ORM\Column(type: "string", length: 8, nullable: true)]
    private ?string $scoredLanguageCode = null;

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

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): self
    {
        $this->languageCode = $languageCode;
        return $this;
    }

    public function getWindowPosition(): int
    {
        return $this->windowPosition;
    }

    public function setWindowPosition(int $windowPosition): self
    {
        $this->windowPosition = $windowPosition;
        return $this;
    }

    public function getWordLengths(): string
    {
        return $this->wordLengths;
    }

    public function setWordLengths(string $wordLengths): self
    {
        $this->wordLengths = $wordLengths;
        return $this;
    }

    public function getDecodedPhrase(): string
    {
        return $this->decodedPhrase;
    }

    public function setDecodedPhrase(string $decodedPhrase): self
    {
        $this->decodedPhrase = $decodedPhrase;
        return $this;
    }

    public function getWordMatches(): ?string
    {
        return $this->wordMatches;
    }

    public function setWordMatches(?string $wordMatches): self
    {
        $this->wordMatches = $wordMatches;
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

    public function getScoredLanguageCode(): ?string
    {
        return $this->scoredLanguageCode;
    }

    public function setScoredLanguageCode(?string $scoredLanguageCode): self
    {
        $this->scoredLanguageCode = $scoredLanguageCode;
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
