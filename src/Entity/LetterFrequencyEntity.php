<?php

namespace App\Entity;

use App\Repository\LetterFrequencyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LetterFrequencyRepository::class)]
#[ORM\Table(name: "letter_frequency")]
#[ORM\Index(name: "idx_language_code", columns: ["language_code"])]
#[ORM\Index(name: "idx_language_frequency", columns: ["language_code", "frequency_score"])]
class LetterFrequencyEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(name: "language_code", length: 10)]
    private string $languageCode;

    #[ORM\Column(length: 10)]
    private string $letter;

    #[ORM\Column(name: "frequency_score", type: "float")]
    private float $frequencyScore;

    #[ORM\Column(name: "ts_created", type: "datetime")]
    private \DateTime $tsCreated;

    public function __construct()
    {
        $this->tsCreated = new \DateTime();
    }

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

    public function getLetter(): string
    {
        return $this->letter;
    }

    public function setLetter(string $letter): self
    {
        $this->letter = $letter;
        return $this;
    }

    public function getFrequencyScore(): float
    {
        return $this->frequencyScore;
    }

    public function setFrequencyScore(float $frequencyScore): self
    {
        $this->frequencyScore = $frequencyScore;
        return $this;
    }

    public function getTsCreated(): \DateTime
    {
        return $this->tsCreated;
    }

    public function setTsCreated(\DateTime $tsCreated): self
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }
}
