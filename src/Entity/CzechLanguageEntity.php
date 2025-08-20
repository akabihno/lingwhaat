<?php

namespace App\Entity;

use App\Repository\CzechLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CzechLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_czech_language")]
class CzechLanguageEntity
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

    public function setId(int $id): CzechLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): CzechLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): CzechLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

}