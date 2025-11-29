<?php

namespace App\Entity;

use App\Repository\SerboCroatianLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerboCroatianLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_serbocroatian_language")]
class SerboCroatianLanguageEntity
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

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): SerboCroatianLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): SerboCroatianLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): SerboCroatianLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): SerboCroatianLanguageEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }
}