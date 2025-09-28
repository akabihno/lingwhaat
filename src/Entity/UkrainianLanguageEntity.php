<?php

namespace App\Entity;

use App\Repository\UkrainianLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UkrainianLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_ukrainian_language")]
class UkrainianLanguageEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 256)]
    private string $name;

    #[ORM\Column]
    private string $ipa;

    #[ORM\Column(name: 'ts_created')]
    private string $tsCreated;

    #[ORM\Column(name: 'unique_pattern_check')]
    private string $uniquePatternCheck;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UkrainianLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): UkrainianLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): UkrainianLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): UkrainianLanguageEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }

    public function getUniquePatternCheck(): string
    {
        return $this->uniquePatternCheck;
    }

    public function setUniquePatternCheck(string $uniquePatternCheck): UkrainianLanguageEntity
    {
        $this->uniquePatternCheck = $uniquePatternCheck;
        return $this;
    }

}