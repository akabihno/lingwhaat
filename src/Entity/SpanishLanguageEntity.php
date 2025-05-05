<?php

namespace App\Entity;

use App\Repository\SpanishLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpanishLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_spanish_language")]
class SpanishLanguageEntity
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

    public function setId(int $id): SpanishLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): SpanishLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): SpanishLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }


}