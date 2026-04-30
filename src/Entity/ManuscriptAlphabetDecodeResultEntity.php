<?php

namespace App\Entity;

use App\Repository\ManuscriptAlphabetDecodeResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManuscriptAlphabetDecodeResultRepository::class)]
#[ORM\Table(name: "manuscript_alphabet_decode_result")]
#[ORM\Index(columns: ["openai_status", "priority_hint"], name: "idx_decode_processing")]
class ManuscriptAlphabetDecodeResultEntity
{
    public const string STATUS_OK = 'ok';
    public const string STATUS_NO_MATCH = 'no_match';
    public const string STATUS_ERROR = 'error';

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
    private string $cipherWords;

    #[ORM\Column(type: "text")]
    private string $wordCandidates;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $selectedPhrase = null;

    #[ORM\Column(type: "string", length: 16, nullable: true)]
    private ?string $openaiStatus = null;

    #[ORM\Column(type: "float", options: ["default" => 0])]
    private float $priorityHint = 0.0;

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

    public function getCipherWords(): string
    {
        return $this->cipherWords;
    }

    public function setCipherWords(string $cipherWords): self
    {
        $this->cipherWords = $cipherWords;
        return $this;
    }

    public function getWordCandidates(): string
    {
        return $this->wordCandidates;
    }

    public function setWordCandidates(string $wordCandidates): self
    {
        $this->wordCandidates = $wordCandidates;
        return $this;
    }

    public function getSelectedPhrase(): ?string
    {
        return $this->selectedPhrase;
    }

    public function setSelectedPhrase(?string $selectedPhrase): self
    {
        $this->selectedPhrase = $selectedPhrase;
        return $this;
    }

    public function getOpenaiStatus(): ?string
    {
        return $this->openaiStatus;
    }

    public function setOpenaiStatus(?string $openaiStatus): self
    {
        $this->openaiStatus = $openaiStatus;
        return $this;
    }

    public function getPriorityHint(): float
    {
        return $this->priorityHint;
    }

    public function setPriorityHint(float $priorityHint): self
    {
        $this->priorityHint = $priorityHint;
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
