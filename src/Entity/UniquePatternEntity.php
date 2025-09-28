<?php

namespace App\Entity;

use App\Repository\UniquePatternRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UniquePatternRepository::class)]
#[ORM\Table(name: "unique_pattern")]
class UniquePatternEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 256)]
    private string $pattern;

    #[ORM\Column]
    private string $position;

    #[ORM\Column]
    private int $count;

    #[ORM\Column(name: 'language_code')]
    private string $languageCode;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UniquePatternEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function setPattern(string $pattern): UniquePatternEntity
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): UniquePatternEntity
    {
        $this->position = $position;
        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): UniquePatternEntity
    {
        $this->count = $count;
        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): UniquePatternEntity
    {
        $this->languageCode = $languageCode;
        return $this;
    }

}