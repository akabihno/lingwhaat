<?php

namespace App\Entity;

use App\Repository\LatvianLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LatvianLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_latvian_language")]
class LatvianLanguageEntity
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

    public function setId(int $id): LatvianLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): LatvianLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): LatvianLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): LatvianLanguageEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }

    public function getUniquePatternCheck(): string
    {
        return $this->uniquePatternCheck;
    }

    public function setUniquePatternCheck(string $uniquePatternCheck): LatvianLanguageEntity
    {
        $this->uniquePatternCheck = $uniquePatternCheck;
        return $this;
    }

}