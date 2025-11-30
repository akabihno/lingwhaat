<?php

namespace App\Entity;

use App\Repository\IcelandicLanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IcelandicLanguageRepository::class)]
#[ORM\Table(name: "pronunciation_icelandic_language")]
class IcelandicLanguageEntity
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

    public function setId(int $id): IcelandicLanguageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): IcelandicLanguageEntity
    {
        $this->name = $name;
        return $this;
    }

    public function getIpa(): string
    {
        return $this->ipa;
    }

    public function setIpa(string $ipa): IcelandicLanguageEntity
    {
        $this->ipa = $ipa;
        return $this;
    }

    public function getTsCreated(): string
    {
        return $this->tsCreated;
    }

    public function setTsCreated(string $tsCreated): IcelandicLanguageEntity
    {
        $this->tsCreated = $tsCreated;
        return $this;
    }

}
