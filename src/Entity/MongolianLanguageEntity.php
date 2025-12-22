<?php

namespace App\Entity;

use App\Repository\MongolianLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MongolianLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_mongolian_language")]
class MongolianLanguageEntity
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

    public function setId(int $id): MongolianLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): MongolianLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): MongolianLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): MongolianLanguageEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }

}
