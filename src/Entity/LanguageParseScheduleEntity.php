<?php

namespace App\Entity;

use App\Repository\LanguageParseScheduleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LanguageParseScheduleRepository::class)]
#[ORM\Table(name: "language_parse_schedule")]
class LanguageParseScheduleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 128)]
    private string $language_name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): LanguageParseScheduleEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getLanguageName(): string
    {
        return $this->language_name;
    }

    public function setLanguageName(string $languageName): LanguageParseScheduleEntity
    {
        $this->language_name = $languageName;
        return $this;
    }

}