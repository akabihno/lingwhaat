<?php

namespace App\Entity;

use App\Repository\WikipediaPatternParseScheduleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WikipediaPatternParseScheduleRepository::class)]
#[ORM\Table(name: "wikipedia_pattern_parse_schedule")]
class WikipediaPatternParseScheduleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "string", length: 8)]
    private string $languageCode;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): WikipediaPatternParseScheduleEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): WikipediaPatternParseScheduleEntity
    {
        $this->languageCode = $languageCode;
        return $this;
    }

}