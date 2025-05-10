<?php

namespace App\Entity;

use App\Repository\EstonianLanguageRepository;

#[ORM\Entity(repositoryClass: EstonianLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_estonian_language")]
class EstonianLanguageEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 256)]
    private string $name;

    #[ORM\Column]
    private string $ipa;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): EstonianLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): EstonianLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): EstonianLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

}